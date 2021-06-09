<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
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
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\GetAllGroupFoldersException;
use OCA\Workspace\Controller\Exceptions\ManageAclGroupFolderException;

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
        IRequest $request,
      	ILogger $logger,
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
     * Returns a list of all the workspaces that the connected user
     * may use.
     *
     * @NoAdminRequired
     * 
     */
    public function getUserWorkspaces() {
        
	// Gets all groupfolders
	$this->logger->debug('Fetching groupfolders');

        $response = $this->groupfolder->getAll();
        $responseBody = json_decode($response->getBody(), true);
        if ( $responseBody['ocs']['meta']['statuscode'] !== 100 ) {

            throw new getAllGroupFoldersException();  

        }

	$spaces = $responseBody['ocs']['data'];
	$this->logger->debug('groupfolders fetched');
	
	// TODO Filter to show only workspaces, not regular groupfolders
	
	// We only want to return those workspaces for which the connected user is a manager
	if (!$this->userService->isUserGeneralAdmin()) {
		$this->logger->debug('Filtering workspaces');
		$filteredSpaces = array_filter($spaces, function($space) {
			return $this->userService->isSpaceManagerOfSpace($space['mount_point']);
		});
        	$spaces = $filteredSpaces;
	}

	// Adds workspace users
	$this->logger->debug('Adding users information to workspaces');
	$spacesWithUsers = array_map(function($space) {
		$users = [];
		foreach($this->groupManager->get(Application::ESPACE_MANAGER_01 . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->userService->formatUser($user, $space));
		};
		$space['admins'] = $users;
		$users = [];
		foreach($this->groupManager->get(Application::ESPACE_USERS_01 . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->userService->formatUser($user, $space));
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

        // Add one group to manage acl

        $dataResponseManageAcl = $this->groupfolder->manageAcl(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceManagerGroup->getGID()
        );

        $responseManageAcl = json_decode($dataResponseManageAcl->getBody(), true);

        if ( $responseManageAcl['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolder->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new ManageAclGroupFolderException();

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
            'acl' => [
                'state' => true,
                'group_manage' => $newSpaceManagerGroup->getGID()
            ],
            'statuscode' => Http::STATUS_CREATED,
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
