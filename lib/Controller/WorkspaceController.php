<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\GroupfolderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Http;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AdvancedPermissionsGroupFolderException;

class WorkspaceController extends Controller {
    
    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var ICredentials */
    private $login;

    /** @var IGroupManager */
    private $groupManager;

    /** @var ILogger */
    private $logger;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var UserService */
    private $userService;

    /** @var GroupfolderService */
    private $groupfolder;

    public function __construct(
        $AppName,
        IClientService $clientService,
        IGroupManager $groupManager,
        ILogger $logger,
        IRequest $request,
        IURLGenerator $urlGenerator,
	    UserService $userService,
        IStore $IStore,
        GroupfolderService $groupfolder
    )
    {
        parent::__construct($AppName, $request);

        $this->groupManager = $groupManager;
        $this->logger = $logger;
        $this->IStore = $IStore;
        $this->urlGenerator = $urlGenerator;
        $this->userService = $userService;

	    $this->login = $this->IStore->getLoginCredentials();

        $this->httpClient = $clientService->newClient();

        $this->groupfolder = $groupfolder;
    }

    /**
     *
     * Given a IUser, returns an array containing all the user information
     * needed for the frontend
     *
     * @param IUser $user
     * @param string $spaceId
     *
     * @return array
     *
     */
    private function formatUser($user, $spaceId) {

	if (is_null($user)) {
		return;
	}

	// Gets the workspace subgroups the user is member of
	$groups = [];
	foreach($this->groupManager->getUserGroups($user) as $group) {
		if (str_ends_with($group->getGID(), $spaceId)) {
			array_push($groups, $group->getGID());
		}
	};   

	// Returns a user that is valid for the frontend
	return array(
		'name' => $user->getDisplayName(),
		'email' => $user->getEmailAddress(),
		'groups' => $groups
	);
    }

    /**
     *
     * Returns a list of all the workspaces that the connected user
     * may use.
     *
     * @NoAdminRequired
     * 
     */
    public function getUserWorkspaces() {
        
	// Gets all groupfolders
	$this->logger->debug('Fetching groupfolders');
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders?format=json',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'OCS-APIRequest' => 'true',
                        'Accept' => 'application/json',
                ],
		'verify' => false,
            ]
        );

	// TODO Check response first
	// TODO Filter to show only workspaces, not regular groupfolders
	
	$spaces = json_decode($response->getBody(), true);
	$spaces = $spaces['ocs']['data'];
	$this->logger->debug('groupfolders fetched', [ 'spaces' => $spaces ]);
	
	// We only want to return those workspaces for which the connected user is a manager
	if (!$this->userService->isUserGeneralAdmin()) {
		$this->logger->debug('Filtering workspaces');
		$filteredSpaces = array_filter($spaces, function($space) {
			return $this->userService->isSpaceManagerOfSpace($space['mount_point']);
		});
        	$spaces = $filteredSpaces;
	}

	// Adds workspace users
	// TODO We still need to get the workspace color here
	$this->logger->debug('Adding users to workspaces');
	$spacesWithUsers = array_map(function($space) {
		$users = [];
		foreach($this->groupManager->get('GE-' . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->formatUser($user, $space['id']));
		};
		$space['admins'] = $users;
		$users = [];
		foreach($this->groupManager->get('U-' . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->formatUser($user, $space['id']));
		};
		$space['users'] = $users;
		return $space;
		
	},$spaces);

	// TODO We still need to get the workspace color here
	
        return new JSONResponse($spacesWithUsers);
    }

    
    /**
     * TODO: https://github.com/arawa/workspace/pull/53
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $spaceName
     * @return JSONResponse with informations from new workspace - { 'msg': Sting, 'statuscode': Int, 'data': Object }
     * @example { 'msg': 'Worspace created', 'statuscode': 201, 'data': Object }
     */
    public function create($spaceName) {

        // create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup('GE-' . $spaceName);
        $newSpaceUsersGroup = $this->groupManager->createGroup('U-' . $spaceName);

        // TODO: add admin group to the app’s « limit to groups » field

        // create groupfolder
        $dataResponseCreateGroupFolder = $this->groupfolder->create($spaceName);

        $responseCreateGroupFolder = json_decode($dataResponseCreateGroupFolder->getBody(), true);
        
        if ( $responseCreateGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            throw new CreateGroupFolderException();  

        }

        // Add groups to groupfolder
        $dataResponseAssignSpaceManagerGroup = $this->groupfolder->addGroup($responseCreateGroupFolder['ocs']['data']['id'], $newSpaceManagerGroup->getGID());

        $responseAssignSpaceManagerGroup = json_decode($dataResponseAssignSpaceManagerGroup->getBody(), true);
        
        if ( $responseAssignSpaceManagerGroup['ocs']['meta']['statuscode'] !== 100 ) {
            
            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolder->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceManagerGroup->getGID());

        }

        $dataResponseAssignSpaceUsersGroup = $this->groupfolder->addGroup(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceUsersGroup->getGID()
        );

        $responseAssignSpaceUsersGroup = json_decode($dataResponseAssignSpaceUsersGroup->getBody(), true);

        if ( $responseAssignSpaceUsersGroup['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolder->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceUsersGroup->getGID());

        }

        // enable ACL
        $dataResponseEnableACLGroupFolder = $this->groupfolder->enableAcl($responseCreateGroupFolder['ocs']['data']['id']);

        $responseEnableACLGroupFolder = json_decode($dataResponseEnableACLGroupFolder->getBody(), true);

        if ( $responseEnableACLGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolder->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AclGroupFolderException();

        }

        // Todo : Replace by apps/groupfolders/folders/$folderId/manageACL => https://github.com/nextcloud/groupfolders/tree/master
        // enable advanced permissions groupfolder
        $dataResponseEnableAdvancedPermissionsGroupFolder = $this->groupfolder->enableAdvancedPermissions(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceManagerGroup->getGID()
        );

        $responseEnableAdvancedPermissionsGroupFolder = json_decode(
            $dataResponseEnableAdvancedPermissionsGroupFolder->getBody(),
            true
        );

        if ( $responseEnableAdvancedPermissionsGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolder->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AdvancedPermissionsGroupFolderException();

        }

        return new JSONResponse([
            'space' => $spaceName,
            'id_space' => $responseCreateGroupFolder['ocs']['data']['id'],
            'groups' => [
                $newSpaceManagerGroup->getGID() => 31,
                $newSpaceUsersGroup->getGID() => 31
            ],
            'space_advanced_permissions' => true,
            'space_assign_groups' => [
                $newSpaceManagerGroup->getGID(),
                $newSpaceUsersGroup->getGID()
            ],
            'statuscode' => Http::STATUS_CREATED,
            'space_acl' => true,
        ]);
    }


    /**
     *
     * TODO This is a single API call. It should probably be moved to the frontend
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @var string $gid
     */
    public function addGroupAdvancedPermissions($folderId, $gid){

        $dataResponse = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/'. $folderId .'/manageACL',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                        'mappingType' => 'group',
                        'mappingId' => $gid,
                        'manageAcl' => true
                ],
                'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'OCS-APIRequest' => 'true',
                        'Accept' => 'application/json',
                ]
            ]
        );

        $response = json_decode($dataResponse->getBody(), true);

        return new JSONResponse($response);
    }
}
