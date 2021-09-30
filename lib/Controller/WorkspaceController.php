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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\BadRequestException;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\GroupfolderService;
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\ManageAclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Service\WorkspaceService;

class WorkspaceController extends Controller {
    
    /** @var IGroupManager */
    private $groupManager;

    /** @var ILogger */
    private $logger;

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
	IGroupManager $groupManager,
	ILogger $logger,
	IRequest $request,
	UserService $userService,
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

	$this->userManager = $userManager;
	$this->userService = $userService;
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

        $newSpaceManagerGroup->setDisplayName(Application::ESPACE_MANAGER_01 . $space->getId());
        $newSpaceUsersGroup->setDisplayName(Application::ESPACE_USERS_01 . $space->getId());
        
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
                ],
                $newSpaceUsersGroup->getGID() => [
                    'gid' => $newSpaceUsersGroup->getGID(),
                    'displayName' => $newSpaceUsersGroup->getDisplayName(),
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
        $this->logger->debug('Removing GE users from the WorkspacesManagers group if needed.');
        $GEGroup = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId);
        foreach ($GEGroup->getUsers() as $user) {
		$this->userService->removeGEFromWM($user, $spaceId);
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
	 *
	 * Returns a list of all the workspaces that the connected user may use.
	 * 
	 * @NoAdminRequired
     * @NoCSRFRequired
	 *
	 */
	public function findAll() {

		$workspaces = $this->workspaceService->getAll();

		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$this->logger->debug('Filtering workspaces');
	    		$filteredWorkspaces = array_filter($workspaces, function($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace['id']);
			});
			$workspaces = $filteredWorkspaces;
		}

		return new JSONResponse($workspaces);

	}

	/**
         * Returns a list of users whose name matches $term
         *
         * @NoAdminRequired
         *
         * @param string $term
         * @param string $spaceId
         *
         * @return JSONResponse
         */
        public function lookupUsers(string $term, string $spaceId) {
                $users = $this->workspaceService->autoComplete($term, $spaceId);
                return new JSONResponse($users);
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
		$GEgroup = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId);

		if ($GEgroup->inGroup($user)) {
			// Changing a user's role from admin to user
			$GEgroup->removeUser($user);
        		$this->logger->debug('Removing a user from a GE group. Removing it from the ' . Application::GROUP_WKSUSER . ' group if needed.');
			$this->userService->removeGEFromWM($user, $spaceId);
		} else {
			// Changing a user's role from user to admin
			$this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId)->addUser($user);
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

		if( $newSpaceName === false ||
			$newSpaceName === null ||
			$newSpaceName === '' 
		) {
			throw new BadRequestException('newSpaceName must be provided');
		}

		// Checks if a space or a groupfolder with this name already exists
		$spaceNameExist = $this->spaceService->checkSpaceNameExist($newSpaceName);
		$groupfolderExist = $this->groupfolderService->checkGroupfolderNameExist($newSpaceName);
		if($spaceNameExist || $groupfolderExist) {
			return new JSONResponse([
				'statuscode' => Http::STATUS_CONFLICT,
				'message' => 'The space name already exist. We cannot rename with this name.'
			]);
		}


		$space = $this->spaceService->updateSpaceName($newSpaceName, (int)$spaceId);
		
		$groupfolder = $this->groupfolderService->get($space->getGroupfolderId());
		$groups = array();
		foreach (array_keys($groupfolder['groups']) as $gid) {
			$groups[$gid] = [
				'gid' => $gid,
				'displayName' => $this->groupManager->get($gid)->getDisplayName(),
			];
		}

        $responseRenameGroupfolder = $this->groupfolderService->rename($space->getGroupfolderId(), $newSpaceName);
        $responseRename = json_decode($responseRenameGroupfolder->getBody(), true);
	    // TODO Handle API call failure (revert space rename and inform user)
        if ($responseRename['ocs']['meta']['statuscode'] === 100) {
            return new JSONResponse([
                "statuscode" => Http::STATUS_NO_CONTENT,
                "space" => $newSpaceName,
                'groups' => $groups
            ]);                    
        } else {
            return new JSONResponse(
                [
                    "statuscode" => Http::STATUS_INTERNAL_SERVER_ERROR,
                    "msg" => "Rename the space is impossible."
                ]
            );
        }
    }

}
