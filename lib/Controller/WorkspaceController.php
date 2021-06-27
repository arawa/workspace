<?php
/**
 *
 * @author Cyrille Bollu <cyrille@bollu.be>
 * @author Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * TODO: Add licence
 *
 */

namespace OCA\Workspace\Controller;

use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\AppFramework\Http;
use OCA\Workspace\Db\Space;
use OCP\Http\Client\IClient;
use OCP\AppFramework\Controller;
use OCA\Workspace\Db\SpaceMapper;
use OCP\Http\Client\IClientService;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\BadRequestException;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\SpaceService;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Workspace\Service\GroupfolderService;
use OCP\Authentication\LoginCredentials\IStore;
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\GetAllGroupFoldersException;
use OCA\Workspace\Controller\Exceptions\ManageAclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Service\WorkspaceService;

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

    /** @var SpaceService */
    private $spaceService;

    /** @var SpaceMapper */
    private $spaceMapper;

    /** @var WorkspaceService */
    private $workspaceService;

    public function __construct(
        $AppName,
        IClientService $clientService,
        IGroupManager $groupManager,
        ILogger $logger,
        IRequest $request,
        IURLGenerator $urlGenerator,
        UserService $userService,
        IStore $IStore,
        GroupfolderService $groupfolderService,
        IUserManager $userManager,
        SpaceMapper $mapper,
        SpaceService $spaceService,
        WorkspaceService $workspaceService
    )
    {
        parent::__construct($AppName, $request);

        $this->groupfolderService = $groupfolderService;
      	$this->groupManager = $groupManager;
        $this->logger = $logger;
        $this->IStore = $IStore;

        $this->urlGenerator = $urlGenerator;
        $this->userManager = $userManager;
        $this->userService = $userService;

        $this->login = $this->IStore->getLoginCredentials();

        $this->httpClient = $clientService->newClient();

        $this->groupfolderService = $groupfolderService;

        $this->spaceMapper = $mapper;

        $this->spaceService = $spaceService;

        $this->workspaceService = $workspaceService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @todo Bug when this code is in SpaceService.php.
     * It has to find a solution to move it.
     */
    public function createSpace(string $spaceName) {

        $spaceNameExist = $this->spaceService->checkSpaceNameExist($spaceName);
        $groupfolderExist = $this->groupfolderService->checkGroupfolderNameExist($spaceName);

        if($spaceNameExist || $groupfolderExist) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_CONFLICT,
                'message' => 'The ' . $spaceName . ' space name already exist'
            ]);
        }

        if( $spaceName === false ||
            $spaceName === null ||
            $spaceName === '' 
        ) {
            throw new BadRequestException('spaceName must be provided');
        }

        // #1 create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup(Application::ESPACE_MANAGER_01 . $spaceName);
        $newSpaceUsersGroup = $this->groupManager->createGroup(Application::ESPACE_USERS_01 . $spaceName);

        // #2 create a groupfolder
        $dataResponseCreateGroupFolder = $this->groupfolderService->create($spaceName);
        
        $responseCreateGroupFolder = json_decode($dataResponseCreateGroupFolder->getBody(), true);
        
        if ( $responseCreateGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            throw new CreateGroupFolderException();  

        }
        // #3 add groups to groupfolder
        $dataResponseAssignSpaceManagerGroup = $this->groupfolderService->addGroup(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceManagerGroup->getGID()
        );

        $responseAssignSpaceManagerGroup = json_decode($dataResponseAssignSpaceManagerGroup->getBody(), true);
        
        if ( $responseAssignSpaceManagerGroup['ocs']['meta']['statuscode'] !== 100 ) {
            
            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceManagerGroup->getGID());

        }

        $dataResponseAssignSpaceUsersGroup = $this->groupfolderService->addGroup(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceUsersGroup->getGID()
        );

        $responseAssignSpaceUsersGroup = json_decode($dataResponseAssignSpaceUsersGroup->getBody(), true);

        if ( $responseAssignSpaceUsersGroup['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceUsersGroup->getGID());

        }

        // #4 enable acl
        $dataResponseEnableACLGroupFolder = $this->groupfolderService->enableAcl($responseCreateGroupFolder['ocs']['data']['id']);

        $responseEnableACLGroupFolder = json_decode($dataResponseEnableACLGroupFolder->getBody(), true);

        if ( $responseEnableACLGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AclGroupFolderException();

        }
        
        // #5 Add one group to manage acl
        $dataResponseManageAcl = $this->groupfolderService->manageAcl(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceManagerGroup->getGID()
        );

        $responseManageAcl = json_decode($dataResponseManageAcl->getBody(), true);

        if ( $responseManageAcl['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new ManageAclGroupFolderException();

        }

        // #6 create the space
        $space = new Space();
        $space->setSpaceName($spaceName);
        $space->setGroupfolderId($responseCreateGroupFolder['ocs']['data']['id']);

        $this->spaceMapper->insert($space);

        return new JSONResponse ([
            'space_name' => $spaceName,
            'id_space' => $space->getId(),
            'folder_id' => $responseCreateGroupFolder['ocs']['data']['id'],
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function destroy($spaceId) {
        
        $spaceResponse = $this->workspaceService->get($spaceId);

        $space = json_decode($spaceResponse->getBody(), true);

        $cloneSpace = $space;

        $responseGroupfolderGet = $this->groupfolderService->get($space['groupfolder_id']);

        $groupfolder = json_decode($responseGroupfolderGet->getBody(), true);
        
        if (
            !$this->userService->isSpaceManagerOfSpace($groupfolder['ocs']['data']['mount_point']) &&
            !$this->userService->isUserGeneralAdmin()
            )
        {
            return new JSONResponse(
                [
                    'data' => [],
                    'http' => [
                        'message' => 'You are not a manager for this space.',
                        'statuscode' => Http::STATUS_FORBIDDEN
                    ]
                ]
            );
        }
        
        $responseGroupfolderDelete = $this->groupfolderService->delete($space['groupfolder_id']);
    
        $groupfolderDelete = json_decode($responseGroupfolderDelete->getBody(), true);

        if ( $groupfolderDelete['ocs']['meta']['statuscode'] !== 100 ) {
            return;
        }

        $groups = [];
        foreach ( array_keys($groupfolder['ocs']['data']['groups']) as $group ) {
            $groups[] = $group;
            $this->groupManager->get($group)->delete();
        }

        return new JSONResponse([
            'http' => [
                'statuscode' => 200,
                'message' => 'The space is deleted.'
            ],
            'data' => [
                'space_name' => $cloneSpace['space_name'],
                'groups' => $groups,
                'space_id' => $cloneSpace['id'],
                'groupfolder_id' => $cloneSpace['groupfolder_id'],
                'state' => 'delete'
            ]
        ]);

    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * TODO: To move or delete.
     */
    public function find($spaceId) {

        $spaceResponse = $this->workspaceService->get($spaceId);
        $space = json_decode($spaceResponse->getBody(), true);

        $groupfolderResponse = $this->groupfolderService->get($space['groupfolder_id']);
        $groupfolder = json_decode($groupfolderResponse->getBody(), true);

        $space['groupfolder_id'] = $groupfolder['ocs']['data']['id'];
        $space['groups'] = $groupfolder['ocs']['data']['groups'];
        $space['quota'] = $groupfolder['ocs']['data']['quota'];
        $space['size'] = $groupfolder['ocs']['data']['size'];
        $space['acl'] = $groupfolder['ocs']['data']['acl'];

        return new JSONResponse($space);
    }

    /**
     * Returns a list of all the workspaces that the connected user
     * may use.
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * TODO: To move or delete.
     */
    public function findAll() {

        $spacesResponse = $this->workspaceService->findAll();

        $spaces = json_decode($spacesResponse->getBody(), true);

        $groupfoldersResponse = $this->groupfolderService->getAll();

        $groupfolders = json_decode($groupfoldersResponse->getBody(), true);

        for($i = 0; $i < count($spaces); $i++) {
            foreach($groupfolders['ocs']['data'] as $key_groupfolders => $value_groupfolders){
                if($key_groupfolders === (int)$spaces[$i]['groupfolder_id']){
                    $spaces[$i]['groupfolder_id'] = $value_groupfolders['id'];
                    $spaces[$i]['groups'] = $value_groupfolders['groups'];
                    $spaces[$i]['quota'] = $value_groupfolders['quota'];
                    $spaces[$i]['size'] = $value_groupfolders['size'];
                    $spaces[$i]['acl'] = $value_groupfolders['acl'];            
                }
            }
        }

        // We only want to return those workspaces for which the connected user is a manager
        if (!$this->userService->isUserGeneralAdmin()) {
            $this->logger->debug('Filtering workspaces');
            $filteredSpaces = array_filter($spaces, function($space) {
                return $this->userService->isSpaceManagerOfSpace($space['space_name']);
            });
            
            $spaces = $filteredSpaces;
        }

        // Adds workspace users
        $this->logger->debug('Adding users information to workspaces');
        $spacesWithUsers = array_map(function($space) {
            $users = array();
            foreach($this->groupManager->get(Application::ESPACE_MANAGER_01 . $space['space_name'])->getUsers() as $user) {
                $users[$user->getDisplayName()] = $this->userService->formatUser($user, $space, 'admin');
            };
            foreach($this->groupManager->get(Application::ESPACE_USERS_01 . $space['space_name'])->getUsers() as $user) {
                $users[$user->getDisplayName()] = $this->userService->formatUser($user, $space, 'user');
            };
            $space['users'] = $users;
            return $space;
            
        },$spaces);


        return new JSONResponse($spacesWithUsers);
    }

	/**
	*
	* Change a user's role in a workspace
	*
	* @NoAdminRequired
	* @SpaceAdminRequired
	*
	* @var string $spaceId
	* @var string $userId
	* 
	*/
	public function changeUserRole(string $spaceId, string $userId) {

		$user = $this->userManager->get($userId);
		$spaceName = $this->groupfolderService->getName($spaceId);
		$GEgroup = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName);

		if ($GEgroup->inGroup($user)) {
			// If user is space manager we may first have to remove it from the list of users allowed to use
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
			// User has not been found in any other workspace manager groups, we must thus remove its
			// access to the application
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
     * @NoCSRFRequired
     * @deprecated use findAll
     */
    public function getUserWorkspaces() {    
	    // Gets all groupfolders
        $this->logger->debug('Fetching groupfolders');

            $response = $this->groupfolderService->getAll();
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
          $group = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $space['mount_point']);
          // TODO Handle is_null($group) better (remove workspace from list?)
          if (!is_null($group)) {
            foreach($group->getUsers() as $user) {
              $users[$user->getDisplayName()] = $this->userService->formatUser($user, $space, 'admin');
            };
          }
          $group = $this->groupManager->get(Application::ESPACE_USERS_01 . $space['mount_point']);
          // TODO Handle is_null($group) better (remove workspace from list?)
          if (!is_null($group)) {
            foreach($group->getUsers() as $user) {
              $users[$user->getDisplayName()] = $this->userService->formatUser($user, $space, 'user');
            };
          }
          $space['users'] = $users;
          return $space;

        },$spaces);

      	// TODO We still need to get the workspace color here

        return new JSONResponse($spacesWithUsers);
    }

    /**
     * 
     * @NoAdminRequired
     * 
     * @var string $spaceName
     * @return JSONResponse with informations from new workspace - { 'msg': Sting, 'statuscode': Int, 'data': Object }
     * @example { 'msg': 'Worspace created', 'statuscode': 201, 'data': Object }
     * @deprecated use createSpace
     */
    public function create($spaceName) {

	// TODO: Make sure only application managers can call this method
	    //
        // create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup(Application::ESPACE_MANAGER_01 . $spaceName);
        $newSpaceUsersGroup = $this->groupManager->createGroup(Application::ESPACE_USERS_01 . $spaceName);

        // TODO: add admin group to the app’s « limit to groups » field

        // create groupfolder
        $dataResponseCreateGroupFolder = $this->groupfolderService->create($spaceName);

        $responseCreateGroupFolder = json_decode($dataResponseCreateGroupFolder->getBody(), true);
        
        if ( $responseCreateGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            throw new CreateGroupFolderException();  

        }

        // Add groups to groupfolder
        $dataResponseAssignSpaceManagerGroup = $this->groupfolderService->addGroup($responseCreateGroupFolder['ocs']['data']['id'], $newSpaceManagerGroup->getGID());

        $responseAssignSpaceManagerGroup = json_decode($dataResponseAssignSpaceManagerGroup->getBody(), true);
        
        if ( $responseAssignSpaceManagerGroup['ocs']['meta']['statuscode'] !== 100 ) {
            
            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceManagerGroup->getGID());

        }

        $dataResponseAssignSpaceUsersGroup = $this->groupfolderService->addGroup(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceUsersGroup->getGID()
        );

        $responseAssignSpaceUsersGroup = json_decode($dataResponseAssignSpaceUsersGroup->getBody(), true);

        if ( $responseAssignSpaceUsersGroup['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();

            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AssignGroupToGroupFolderException($newSpaceUsersGroup->getGID());

        }

        // enable ACL
        $dataResponseEnableACLGroupFolder = $this->groupfolderService->enableAcl($responseCreateGroupFolder['ocs']['data']['id']);

        $responseEnableACLGroupFolder = json_decode($dataResponseEnableACLGroupFolder->getBody(), true);

        if ( $responseEnableACLGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AclGroupFolderException();

        }

        // Add one group to manage acl
        $dataResponseManageAcl = $this->groupfolderService->manageAcl(
            $responseCreateGroupFolder['ocs']['data']['id'],
            $newSpaceManagerGroup->getGID()
        );

        $responseManageAcl = json_decode($dataResponseManageAcl->getBody(), true);

        if ( $responseManageAcl['ocs']['meta']['statuscode'] !== 100 ) {

            $newSpaceManagerGroup->delete();
            $newSpaceUsersGroup->delete();
            
            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new ManageAclGroupFolderException();

        }

        return new JSONResponse([
            'space_name' => $spaceName,
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
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param int $spaceId
     * @param string $newSpaceName
     * @return JSONResponse
     * 
     * @todo Check if the space name and the group name exist or not.
     * TODO: Manage errors & may be refactor
     * groupfolder->rename & groupfolder->attachGroup.
     */
    public function renameSpace($spaceId, $newSpaceName) {

        $responseSpace = $this->workspaceService->get($spaceId);
        $currentSpace = json_decode($responseSpace->getBody(), true);

        if (!$this->userService->isSpaceManagerOfSpace($currentSpace['space_name']) &&
            !$this->userService->isUserGeneralAdmin() ) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_UNAUTHORIZED,
                'message' => 'You are not authorized to rename the ' . $currentSpace['space_name'] . ' space.'
            ]);
        }


        $responseSpace = $this->workspaceService->updateSpaceName($newSpaceName, (int)$spaceId);
        $space = json_decode($responseSpace->getBody(), true);


        $currentSpaceName = $currentSpace['space_name'];
     
        $responseGroupfolder = $this->groupfolderService->rename($space['groupfolder_id'], $newSpaceName);

        $responseRename = json_decode($responseGroupfolder->getBody(), true);

        if( $responseRename['ocs']['meta']['statuscode'] === 100 ) {
            $response = [
                "statuscode" => Http::STATUS_NO_CONTENT,
                "space" => $newSpaceName
            ];
            
            $groupGE = $this->groupManager->get('GE-' . $currentSpaceName);
            $groupU = $this->groupManager->get('U-' . $currentSpaceName);

            $IUsersGE = $groupGE->getUsers();
            $IUsersU = $groupU->getUsers();
            
            $newGroupGE = $this->groupManager->createGroup('GE-' . $newSpaceName);
            $newGroupU = $this->groupManager->createGroup('U-' . $newSpaceName);

            foreach ($IUsersGE as $IUserGE) {
                $newGroupGE->addUser($IUserGE);
            }

            foreach ($IUsersU as $IUserU) {
                $newGroupU->addUser($IUserU);
            }

            $respAttachGroupGE = $this->groupfolderService->attachGroup($space['groupfolder_id'], $newGroupGE->getGID());
            
            if ($respAttachGroupGE->getStatusCode() === 200) {
                $response['groups'][] = $newGroupGE->getGID();
            }

            $respAttachGroupU = $this->groupfolderService->attachGroup($space['groupfolder_id'], $newGroupU->getGID());

            if ($respAttachGroupU->getStatusCode() === 200) {
                $response['groups'][] = $newGroupU->getGID();
            }
        
            $groupGE->delete();
            $groupU->delete();
        }

        return new JSONResponse($response);
    }

    /**
     * @NoAdminRequired
     * @SpaceAdminRequired
     * 
     * TODO: Manage errors & may be refactor
     * groupfolder->rename & groupfolder->attachGroup.
     * 
     * @param int $folderId
     * @param string $newSpaceName
     * @return JSONResponse
     * @deprecated use renameSpace
     */
    public function rename($folderId, $newSpaceName) {

        $responseCurrentSpaceName = $this->groupfolderService->get($folderId);
        $currentSpaceName = json_decode($responseCurrentSpaceName->getBody(), true);
        $currentMountPointSpaceName = $currentSpaceName['ocs']['data']['mount_point'];
     
        $responseGroupfolder = $this->groupfolderService->rename($folderId, $newSpaceName);

        $responseRename = json_decode($responseGroupfolder->getBody(), true);

        if( $responseRename['ocs']['meta']['statuscode'] === 100 ) {
            $response = [
                "statuscode" => Http::STATUS_NO_CONTENT,
                "space" => $newSpaceName
            ];
            
            $groupGE = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $currentMountPointSpaceName);
            $groupU = $this->groupManager->get(Application::ESPACE_USERS_01 . $currentMountPointSpaceName);

            $IUsersGE = $groupGE->getUsers();
            $IUsersU = $groupU->getUsers();
            
            $newGroupGE = $this->groupManager->createGroup(Application::ESPACE_MANAGER_01 . $newSpaceName);
            $newGroupU = $this->groupManager->createGroup(Application::ESPACE_USERS_01 . $newSpaceName);

            foreach ($IUsersGE as $IUserGE) {
                $newGroupGE->addUser($IUserGE);
            }

            foreach ($IUsersU as $IUserU) {
                $newGroupU->addUser($IUserU);
            }

            $respAttachGroupGE = $this->groupfolderService->attachGroup($folderId, $newGroupGE->getGID());
            
            if ($respAttachGroupGE->getStatusCode() === 200) {
                $response['groups'][] = $newGroupGE->getGID();
            }

            $respAttachGroupU = $this->groupfolderService->attachGroup($folderId, $newGroupU->getGID());

            if ($respAttachGroupU->getStatusCode() === 200) {
                $response['groups'][] = $newGroupU->getGID();
            }
        
            $groupGE->delete();
            $groupU->delete();
        }

        return new JSONResponse($response);
    }
  
	/**
	*
	* Removes a user from a workspace
	*
	* @NoAdminRequired
	* @SpaceAdminRequired
	*
	* @var string $spaceId
	* @var string $userId
	* 
	*/
	public function removeUserFromWorkspace(string $spaceId, string $userId) {
		$this->logger->debug('Removing user ' . $userId . ' from workspace ' . $spaceId);

		$user = $this->userManager->get($userId);
		$spaceName = $this->groupfolderService->getName($spaceId);
		$GEgroup = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName);

		// If user is a general manager we may first have to remove it from the list of users allowed to use
		// the application
		if ($GEgroup->inGroup($user)) {
			$this->logger->debug('User is admin of the workspace, figuring out if we must remove it from the general workspace admins group.');
			$found = false;
			$groups = $this->groupManager->getUserGroups($user);
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
				$this->logger->debug('User is not admin of any other workspace, removing it from the general workspace admins group.');
				$workspaceUserGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
				$workspaceUserGroup->removeUser($user);
			}
		}

		// We can now blindly remove the user from the space's admin and user groups
		$this->logger->debug('Removing user from workspace.');
		$GEgroup->removeUser($user);
		$UserGroup = $this->groupManager->get(Application::ESPACE_USERS_01 . $spaceName);
		$UserGroup->removeUser($user);

		return new JSONResponse();
	}

    /**
     * @NoAdminRequired
     * 
     * @deprecated use destroy
     * 
     * @var string $folderId
     * @return JSONResponse
     */
    public function delete($folderId) {

        $groups = [];
    
        $responseGroupfolderGet = $this->groupfolderService->get($folderId);
        $groupfolder = json_decode($responseGroupfolderGet->getBody(), true);

        $responseGroupfolderDelete = $this->groupfolderService->delete($folderId);
        $groupfolderDelete = json_decode($responseGroupfolderDelete->getBody(), true);

        foreach ( array_keys($groupfolder['ocs']['data']['groups']) as $group ) {
            $groups[] = $group;
            $this->groupManager->get($group)->delete();
        }

        if ( $groupfolderDelete['ocs']['meta']['statuscode'] !== 100 ) {
            return;
        }

        return new JSONResponse([
            'http' => [
                'statuscode' => 200,
                'message' => 'The space is deleted.'
            ],
            'data' => [
                'space_name' => $groupfolder['ocs']['data']['mount_point'],
                'groups' => $groups,
                'state' => 'delete'
            ]
        ]);

    }
    
}
