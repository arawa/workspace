<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\GetAllGroupFoldersException;
use OCA\Workspace\Controller\Exceptions\ManageAclGroupFolderException;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\GroupfolderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

class WorkspaceController extends Controller {
    
    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var IGroupManager */
    private $groupManager;

    /** @var ILogger */
    private $logger;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IUserManager */
    private $userManager;

    /** @var UserService */
    private $userService;

    /** @var GroupfolderService */
    private $groupfolderService;

    public function __construct(
        $AppName,
        GroupfolderService $groupfolderService,
        IClientService $clientService,
        IGroupManager $groupManager,
        IRequest $request,
      	ILogger $logger,
        IStore $IStore
        IURLGenerator $urlGenerator,
	IUserManager $userManager,
	UserService $userService
    )
    {
        parent::__construct($AppName, $request);
        $this->groupfolderService = $groupfolderService;
      	$this->groupManager = $groupManager;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->userManager = $userManager;
        $this->userService = $userService;

        $this->login = $this->IStore->getLoginCredentials();
        $this->httpClient = $clientService->newClient();

    }

	/**
	*
	* Change a user's role in a workspace
	*
	* @NoAdminRequired
	*
	* @var string $spaceName
	* @var string $userName
	* 
	*/
	public function changeUserRole(string $spaceName, string $userName) {
		if (!$this->userService->isSpaceManagerOfSpace($spaceName) && !$this->userService->isUserGeneralAdmin()) {
			return new JSONResponse(['You are not a manager for this space'], Http::STATUS_FORBIDDEN);
		}

		$user = $this->userManager->get($userName);
		$GEgroup = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName);

		if ($GEgroup->inGroup($user)) {
			// If user is a general manager we may first have to remove it from the list of users allowed to use
			// the application
			$groups = $this->groupManager->getUserGroups($user);
			$found = false;
			foreach($groups as $group) {
				$groupName = $group->getGID();
				if (strpos($groupName, Application::ESPACE_MANAGER_01) === 0 &&
					$groupName !== Application::ESPACE_MANAGER_01 . $spaceName &&
					$groupName !== Application::GROUP_WKSUSER
				) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$workspaceUserGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
				$workspaceUserGroup->removeUser($user);
			}
			// We can now remove the user from the space's admin group
			$GEgroup->removeUser($user);
			// And add it to the space's user group
			$this->groupManager->get(Application::ESPACE_USERS_01 . $spaceName)->addUser($user);
		} else {
			$this->groupManager->get(Application::ESPACE_USERS_01 . $spaceName)->removeUser($user);
			$this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName)->addUser($user);
			$this->groupManager->get(Application::GROUP_WKSUSER)->addUser($user);
		}

	        return new JSONResponse();
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
		$users = array();
		foreach($this->groupManager->get(Application::ESPACE_MANAGER_01 . $space['mount_point'])->getUsers() as $user) {
			$users[$user->getDisplayName()] = $this->userService->formatUser($user, $space['id'], 'admin');
		};
		$space['admins'] = $users;
		$users = array();
		foreach($this->groupManager->get(Application::ESPACE_USERS_01 . $space['mount_point'])->getUsers() as $user) {
			$users[$user->getDisplayName()] = $this->userService->formatUser($user, $space['id'], 'user');
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
	* Removes a user from a workspace
	*
	* @NoAdminRequired
	*
	* @var string $spaceName
	* @var string $userName
	* 
	*/
	public function removeUserFromWorkspace(string $spaceName, string $userName) {
		if (!$this->userService->isSpaceManagerOfSpace($spaceName) && !$this->userService->isUserGeneralAdmin()) {
			return new JSONResponse(['You are not a manager for this space'], Http::STATUS_FORBIDDEN);
		}

		$user = $this->userManager->get($userName);
		$GEgroup = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName);

		// If user is a general manager we may first have to remove it from the list of users allowed to use
		// the application
		if ($GEgroup->inGroup($user)) {
			$groups = $this->groupManager->getUserGroups($user);
			$found = false;
			foreach($groups as $group) {
				$groupName = $group->getGID();
				if (strpos($groupName, Application::ESPACE_MANAGER_01) === 0 &&
					$groupName !== Application::ESPACE_MANAGER_01 . $spaceName &&
					$groupName !== Application::GROUP_WKSUSER
				) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$workspaceUserGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
				$workspaceUserGroup->removeUser($user);
			}
		}

		// We can now blindly remove the user from the space's admin and user groups
		$GEgroup->removeUser($user);
		$UserGroup = $this->groupManager->get(Application::ESPACE_USERS_01 . $spaceName);
		$UserGroup->removeUser($user);

	        return new JSONResponse();
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
