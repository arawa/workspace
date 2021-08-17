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
     * @GeneralManagerRequired
     * @NoCSRFRequired
     * @todo Bug when this code is in SpaceService.php.
     * It has to find a solution to move it.
     */
    public function createSpace(string $spaceName) {

        if( $spaceName === false ||
            $spaceName === null ||
            $spaceName === '' 
        ) {
            throw new BadRequestException('spaceName must be provided');
        }

	// Checks if a space or a groupfolder with this name already exists
	$spaceNameExist = $this->spaceService->checkSpaceNameExist($spaceName);
	$groupfolderExist = $this->groupfolderService->checkGroupfolderNameExist($spaceName);
	if($spaceNameExist || $groupfolderExist) {
	    return new JSONResponse([
		'statuscode' => Http::STATUS_CONFLICT,
		'message' => 'The ' . $spaceName . ' space name already exist'
	    ]);
	}

        // #1 create a groupfolder
        $dataResponseCreateGroupFolder = $this->groupfolderService->create($spaceName);
        $responseCreateGroupFolder = json_decode($dataResponseCreateGroupFolder->getBody(), true);
	if ( $responseCreateGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {

	    throw new CreateGroupFolderException();  

	}
        
        // #2 enable acl
        $dataResponseEnableACLGroupFolder = $this->groupfolderService->enableAcl($responseCreateGroupFolder['ocs']['data']['id']);

        $responseEnableACLGroupFolder = json_decode($dataResponseEnableACLGroupFolder->getBody(), true);

        if ( $responseEnableACLGroupFolder['ocs']['meta']['statuscode'] !== 100 ) {
            
            $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

            throw new AclGroupFolderException();

        }

        // #3 create the space
        $space = new Space();
        $space->setSpaceName($spaceName);
        $space->setGroupfolderId($responseCreateGroupFolder['ocs']['data']['id']);
        $space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
        $this->spaceMapper->insert($space);
        
        // #4 create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space->getId());
        $newSpaceUsersGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_USERS_01 . $space->getId());

        $newSpaceManagerGroup->setDisplayName(Application::ESPACE_MANAGER_01 . $spaceName);
        $newSpaceUsersGroup->setDisplayName(Application::ESPACE_USERS_01 . $spaceName);
        
        // #5 add U group to groupfolder
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

        // #6 add GE group to groupfolder
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

	// #7 Add one group to manage acl
	$dataResponseManageAcl = $this->groupfolderService->manageAcl(
	    $responseCreateGroupFolder['ocs']['data']['id'],
	    $newSpaceManagerGroup->getGID()
	);
	$responseManageAcl = json_decode($dataResponseManageAcl->getBody(), true);
	if ( $responseManageAcl['ocs']['meta']['statuscode'] !== 100 ) {

	    $this->groupfolderService->delete($responseCreateGroupFolder['ocs']['data']['id']);

	    throw new ManageAclGroupFolderException();

	}

	// #8 Returns result
        return new JSONResponse ([
            'space_name' => $spaceName,
            'id_space' => $space->getId(),
            'folder_id' => $responseCreateGroupFolder['ocs']['data']['id'],
            'color' => $space->getColorCode(),
            'groups' => [
                $newSpaceManagerGroup->getGID() => [
                    'gid' => $newSpaceManagerGroup->getGID(),
                    'displayName' => $newSpaceManagerGroup->getDisplayName(),
                    'permissions_groupfolder' => 31
                ],
                $newSpaceUsersGroup->getGID() => [
                    'gid' => $newSpaceUsersGroup->getGID(),
                    'displayName' => $newSpaceUsersGroup->getDisplayName(),
                    'permissions_groupfolder' => 31
                ]
            ],
            'space_advanced_permissions' => true,
            'space_assign_groups' => [
                $newSpaceManagerGroup->getDisplayName(),
                $newSpaceUsersGroup->getDisplayName()
            ],
            'acl' => [
                'state' => true,
                'group_manage' => $newSpaceManagerGroup->getDisplayName()
            ],
            'statuscode' => Http::STATUS_CREATED,
        ]);
    }

    /**
     *
     * Deletes the workspace, and the corresponding groupfolder and groups
     *
     * @NoAdminRequired
     * @SpaceAdminRequired
     *
     */
    public function destroy($spaceId) {
	$this->logger->debug('Deleting space ' . $spaceId);
        $space = $this->workspaceService->get($spaceId);

	$this->logger->debug('Removing correesponding groupfolder.');
	$groupfolder = $this->groupfolderService->get($space['groupfolder_id']);
        $resp = $this->groupfolderService->delete($space['groupfolder_id']);
        if ( $resp !== 100 ) {
		// TODO Should return an error
            	return;
        }

	// Delete all GE from WorkspacesManagers group if necessary
	// TODO: Lookup would be much more consistent if we were using gid instead of displayName here
	$this->logger->debug('Removing GE users from the WorkspacesManagers group if needed.');
	$GEGroups = $this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name']);
	foreach($GEGroups as $group) {
		foreach ($group->getUsers() as $user) {
			$this->userService->removeGEFromWM($user, $space);
		}
	}

	// Removes all workspaces groups
        $groups = [];
	$this->logger->debug('Removing workspaces groups.');
        foreach ( array_keys($groupfolder['groups']) as $group ) {
            $groups[] = $group;
            $this->groupManager->get($group)->delete();
        }
	
	return new JSONResponse([
            'http' => [
                'statuscode' => 200,
                'message' => 'The space is deleted.'
            ],
            'data' => [
                'space_name' => $space['space_name'],
                'groups' => $groups,
                'space_id' => $space['id'],
                'groupfolder_id' => $space['groupfolder_id'],
                'state' => 'delete'
            ]
        ]);

    }

    /**
     * Returns a list of all the workspaces that the connected user
     * may use.
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     *
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
		return $this->userService->isSpaceManagerOfSpace($space['id']);
	    });
	    
	    $spaces = $filteredSpaces;
	}

        // Adds workspace users and groups details
        // Caution: It is important to add users from the workspace's user group before adding the users
        // from the workspace's manager group, as users may be members of both groups 
        $this->logger->debug('Adding users information to workspaces');
        $workspaces = array_map(function($space) {
            // Adds users
            $users = array();
            $group = $this->groupManager->search(Application::ESPACE_USERS_01 . $space['space_name'])[0];
            // TODO Handle is_null($group) better (remove workspace from list?)
            if (!is_null($group)) {
                foreach($group->getUsers() as $user) {
                    $users[$user->getUID()] = $this->userService->formatUser($user, $space, 'user');
                };
            }
            // TODO Handle is_null($group) better (remove workspace from list?)
            $group = $this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name'])[0];
            if (!is_null($group)) {
                foreach($group->getUsers() as $user) {
                    $users[$user->getUID()] = $this->userService->formatUser($user, $space, 'admin');
                };
            }
            $space['users'] = (object) $users;

            // Adds groups
            $groups = array();
            foreach (array_keys($space['groups']) as $gid) {
                $NCGroup = $this->groupManager->get($gid);
                $groups[$gid] = array(
                    'gid' => $NCGroup->getGID(),
                    'displayName' => $NCGroup->getDisplayName()
                );
            }
            $space['groups'] = $groups;

            return $space;

        },$spaces);

        return new JSONResponse($workspaces);
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
		$space = $this->workspaceService->get($spaceId);
		$GEgroup = $this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name'])[0];

		// Checks if user is member of the workspace's manager group
		if ($GEgroup->inGroup($user)) {
			// If user is space manager we may first have to remove it from the list of users allowed to use
			// the application
			$groups = $this->groupManager->getUserGroups($user);
			$found = false;
			foreach($groups as $group) {
				$groupName = $group->getDisplayName();
				if (strpos($groupName, Application::ESPACE_MANAGER_01) === 0 &&
					$groupName !== Application::ESPACE_MANAGER_01 . $space['space_name'] &&
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
			$this->groupManager->search(Application::ESPACE_USERS_01 . $space['space_name'])[0]->addUser($user);
		} else {
			$this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name'])[0]->addUser($user);
			$this->groupManager->get(Application::GROUP_WKSUSER)->addUser($user);
		}

		return new JSONResponse();
	}

    /**
     * 
     * @NoAdminRequired
     * @SpaceAdminRequired
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

        $space = $this->spaceService->updateSpaceName($newSpaceName, (int)$spaceId);
     
        $groupfolder = $this->groupfolderService->get($space->getGroupfolderId());
        $groupsFromGroupfolder = array_diff_key($groupfolder['groups'], [ 
            Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId => 31,
            Application::GID_SPACE . Application::ESPACE_USERS_01 . $spaceId => 31
        ]);
        foreach(array_keys($groupsFromGroupfolder) as $groupname){
            $group = $this->groupManager->get($groupname);

            $groups[$group->getGID()] = [
                "displayName" => $group->getDisplayName(),
                "gid"   => $group->getGID()
            ];
        }

        $responseRenameGroupfolder = $this->groupfolderService->rename($space->getGroupfolderId(), $newSpaceName);
        $responseRename = json_decode($responseRenameGroupfolder->getBody(), true);
	    // TODO Handle API call failure (revert space rename and inform user)
        if( $responseRename['ocs']['meta']['statuscode'] === 100 ) {
            
            $groupGE = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId);
            $groupU = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_USERS_01 . $spaceId);

            $groupGE->setDisplayName(Application::ESPACE_MANAGER_01 . $newSpaceName);
            $groupU->setDisplayName(Application::ESPACE_USERS_01 . $newSpaceName);

            $groups[$groupGE->getGID()] = [
                "displayName" => $groupGE->getDisplayName(),
                "gid" => $groupGE->getGID()
            ];

            $groups[$groupU->getGID()] = [
                "displayName" => $groupU->getDisplayName(),
                "gid" => $groupU->getGID()
            ];
        
        }

        return new JSONResponse([
            "statuscode" => Http::STATUS_NO_CONTENT,
            "space" => $newSpaceName,
            'groups' => $groups
        ]);
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
		$space = $this->workspaceService->get($spaceId);
		$GEgroup = $this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name'])[0];

		// If user is a general manager we may first have to remove it from the list of users allowed to use
		// the application
		if ($GEgroup->inGroup($user)) {
			$this->logger->debug('User is manager of the workspace, removing it from the WorkspacesManagers group if needed.');
			$this->userService->removeGEFromWM($user, $space);
		}

		// We can now blindly remove the user from the space's admin and user groups
		$this->logger->debug('Removing user from workspace.');
		$GEgroup->removeUser($user);
		$UserGroup = $this->groupManager->search(Application::ESPACE_USERS_01 . $space['space_name'])[0];
		$UserGroup->removeUser($user);

		// Removes user from all 'subgroups' when we remove it from the workspace's user group
		foreach(array_keys($space['groups']) as $gid) {
			$NCGroup = $this->groupManager->get($gid)->removeUser($user);
		}

		return new JSONResponse();
	}
}
