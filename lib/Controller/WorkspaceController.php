<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

    /** @var SpaceService */
    private $spaceService;

    /** @var SpaceMapper */
    private $spaceMapper;

    /** @var WorkspaceService */
    private $workspaceService;

    private const REGEX_CHECK_NOTHING_SPECIAL_CHARACTER = '/[~<>{}|;.:,!?\'@#$+()%\\\^=\/&*\[\]]/';

    public function __construct(
	$AppName,
	IGroupManager $groupManager,
	ILogger $logger,
	IRequest $request,
	UserService $userService,
	IUserManager $userManager,
	SpaceMapper $mapper,
	SpaceService $spaceService,
	WorkspaceService $workspaceService
    )
    {
	parent::__construct($AppName, $request);

	$this->groupManager = $groupManager;
	$this->logger = $logger;

	$this->userManager = $userManager;
	$this->userService = $userService;

	$this->spaceMapper = $mapper;
	$this->spaceService = $spaceService;
	$this->workspaceService = $workspaceService;
    }

    /**
     * Check if the space name contains specials characters or a blank into the end its name.
     * @param string $spaceName
     * @return object if there is an error
     */
    private function checkTheSpaceName(string $spaceName) {
        if (preg_match($this::REGEX_CHECK_NOTHING_SPECIAL_CHARACTER, $spaceName)) {
            return [
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) - % \ ^ = / & * ]',
            ];
        }

        return [];
    }

    /**
     * @param string $spaceName it's the space name
     * @return string whithout the blank to start and end of the space name
     */
    private function deleteBlankSpaceName(string $spaceName) {
        return trim($spaceName);
    }

    /**
     * @NoAdminRequired
     * @GeneralManagerRequired
     * @NoCSRFRequired
     */
    public function createSpace(string $spaceName, int $folderId) {
        if ( $spaceName === false ||
            $spaceName === null ||
            $spaceName === '' 
        ) {
            throw new BadRequestException('spaceName must be provided');
        }

        $errorInSpaceName = $this->checkTheSpaceName($spaceName);

        if (!empty($errorInSpaceName)) {
            return new JSONResponse($errorInSpaceName);
        }

        $spaceNameExist = $this->spaceService->checkSpaceNameExist($spaceName);

        if($spaceNameExist) {
            return new JSONResponse([
				'statuscode' => Http::STATUS_CONFLICT,
				'message' => 'The ' . $spaceName . ' space name already exist'
            ]);
        }

        $spaceName = $this->deleteBlankSpaceName($spaceName);

        // #1 create the space
        $space = new Space();
        $space->setSpaceName($spaceName);
        $space->setGroupfolderId($folderId);
        $space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
        $this->spaceMapper->insert($space);

        if (is_null($space)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a space.',
            ]);
        }
        
        // #2 create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space->getId());

        if (is_null($newSpaceManagerGroup)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a Space Manager group.',
            ]);
        }
        
        $newSpaceUsersGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_USERS_01 . $space->getId());

        if (is_null($newSpaceUsersGroup)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a Space Users group.',
            ]);
        }

        $newSpaceManagerGroup->setDisplayName(Application::ESPACE_MANAGER_01 . $space->getId());
        $newSpaceUsersGroup->setDisplayName(Application::ESPACE_USERS_01 . $space->getId());
        
		// #3 Returns result
        return new JSONResponse ([
            'space_name' => $space->getSpaceName(),
            'id_space' => $space->getId(),
            'folder_id' => $space->getGroupfolderId(),
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
            'statuscode' => Http::STATUS_CREATED,
        ]);
    }

    /**
     * @NoAdminRequired
     * @GeneralManagerRequired
     * @NoCSRFRequired
     */
    public function convertGroupfolderToSpace(string $spaceName, $groupfolder) {

        if ( $spaceName === false ||
            $spaceName === null ||
            $spaceName === '' 
        ) {
            throw new BadRequestException('spaceName must be provided');
        }

        $errorInSpaceName = $this->checkTheSpaceName($spaceName);

        if (!empty($errorInSpaceName)) {
            return new JSONResponse($errorInSpaceName);
        }
    
        $spaceName = $this->deleteBlankSpaceName($spaceName);

        if (gettype($groupfolder) === 'string') {
			$groupfolder = json_decode($groupfolder, true);
		}
        $spaceNameExist = $this->spaceService->checkSpaceNameExist($spaceName);
        if($spaceNameExist) {
            return new JSONResponse([
				'statuscode' => Http::STATUS_CONFLICT,
				'message' => 'The ' . $spaceName . ' space name already exist'
            ]);
        }

        // #1 create the space
        $space = new Space();
        $space->setSpaceName($spaceName);
        $space->setGroupfolderId($groupfolder['id']);
        $space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
        $this->spaceMapper->insert($space);

        if (is_null($space)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a space.',
            ]);
        }
        
        // #2 create groups
        $newSpaceManagerGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space->getId());

        if (is_null($newSpaceManagerGroup)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a Space Manager group.',
            ]);
        }
        
        $newSpaceUsersGroup = $this->groupManager->createGroup(Application::GID_SPACE . Application::ESPACE_USERS_01 . $space->getId());

        if (is_null($newSpaceUsersGroup)) {
            return new JSONResponse([
                'statuscode' => Http::STATUS_BAD_REQUEST,
                'message' => 'Error to create a Space Users group.',
            ]);
        }

        $newSpaceManagerGroup->setDisplayName(Application::ESPACE_MANAGER_01 . $space->getId());
        $newSpaceUsersGroup->setDisplayName(Application::ESPACE_USERS_01 . $space->getId());
        
        $groupsName = array_keys($groupfolder['groups']);

        $groupsOfGroupfolder = [];
        $users = [];
        // To cheat the fomatUser method.
        $groupfolder['groups'][$newSpaceUsersGroup->getGID()] = 31;
        foreach($groupsName as $groupName) {
            $group = $this->groupManager->get($groupName);

            $usersOfAGroup = $group->getUsers();
            foreach($usersOfAGroup as $user) {                
                $newSpaceUsersGroup->addUser($user);
                $users[$user->getUID()] = $this->userService->formatUser($user, $groupfolder, 'user');
            }
            $groupsOfGroupfolder[$group->getGID()] = [
                    'gid' => $group->getGID(),
                    'displayName' => $group->getDisplayName(),
                ];
        }
        $groups = array_merge(
            $groupsOfGroupfolder,
            [
                $newSpaceManagerGroup->getGID() => [
                    'gid' => $newSpaceManagerGroup->getGID(),
                    'displayName' => $newSpaceManagerGroup->getDisplayName(),
                ],
                $newSpaceUsersGroup->getGID() => [
                    'gid' => $newSpaceUsersGroup->getGID(),
                    'displayName' => $newSpaceUsersGroup->getDisplayName(),
                ]
            ]
        );

		// #3 Returns result
        return new JSONResponse ([
            'space_name' => $space->getSpaceName(),
            'id_space' => $space->getId(),
            'folder_id' => $space->getGroupfolderId(),
            'color' => $space->getColorCode(),
            'groups' => $groups,
            'users' => (object)$users,
            'statuscode' => Http::STATUS_CREATED,
        ]);
    }

  /**
     *
     * Deletes the workspace, and the corresponding groupfolder and groups
     *
     * @NoAdminRequired
     * @SpaceAdminRequired
     * @param object $workspace
     * 
     */
    public function destroy($workspace) {

        $this->logger->debug('Removing GE users from the WorkspacesManagers group if needed.');
        $GEGroup = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $workspace['id']);
        foreach ($GEGroup->getUsers() as $user) {
		$this->userService->removeGEFromWM($user, $workspace['id']);
        }

	    // Removes all workspaces groups
        $groups = [];
    	$this->logger->debug('Removing workspaces groups.');
        foreach ( array_keys($workspace['groups']) as $group ) {
            $groups[] = $group;
            $this->groupManager->get($group)->delete();
        }

	    return new JSONResponse([
            'http' => [
                'statuscode' => 200,
                'message' => 'The space is deleted.'
            ],
            'data' => [
                'space_name' => $workspace['name'],
                'groups' => $groups,
                'space_id' => $workspace['id'],
                'groupfolder_id' => $workspace['groupfolderId'],
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
	    		$filteredWorkspaces = array_values(array_filter($workspaces, function($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace['id']);
			}));
			$workspaces = $filteredWorkspaces;
		}

		return new JSONResponse($workspaces);

	}

    /**
     * @NoAdminRequired
     * @param string|object $workspace
     * @return JSONResponse
     */
    public function addGroupsInfo($workspace) {
        return new JSONResponse($this->workspaceService->addGroupsInfo($workspace));
    }

    /**
     * @NoAdminRequired
     * @param string|object $workspace
     * @return JSONResponse
     */
    public function addUsersInfo($workspace) {
        return new JSONResponse($this->workspaceService->addUsersInfo($workspace));
    }
    
	/**
     * Returns a list of users whose name matches $term
     *
     * @NoAdminRequired
     * @param string $term
     * @param string $spaceId
     * @param string|object $space
     *
     * @return JSONResponse
     */
    public function lookupUsers(string $term, string $spaceId, $space) {
        if (gettype($space) === 'string') {
            $space = json_decode($space, true);
        }
        $users = $this->workspaceService->autoComplete($term, $space);
        return new JSONResponse($users);
    }

	/**
	*
	* Change a user's role in a workspace
	*
	* @NoAdminRequired
	* @SpaceAdminRequired
	*
	* @param object|string $space
	* @param string $userId
	* 
	*/
	public function changeUserRole($space, string $userId) {

        if (gettype($space) === 'string') {
			$space = json_decode($space, true);
		}

		$user = $this->userManager->get($userId);
        $GEgroup = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space['id']);

		if ($GEgroup->inGroup($user)) {
			// Changing a user's role from admin to user
			$GEgroup->removeUser($user);
        		$this->logger->debug('Removing a user from a GE group. Removing it from the ' . Application::GROUP_WKSUSER . ' group if needed.');
			$this->userService->removeGEFromWM($user, $space['id']);
		} else {
			// Changing a user's role from user to admin
			$this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space['id'])->addUser($user);
			$this->groupManager->get(Application::GROUP_WKSUSER)->addUser($user);
		}

		return new JSONResponse();
	}

    /**
     * 
     * @NoAdminRequired
     * @SpaceAdminRequired
     * @NoCSRFRequired
     * @param object|string $workspace
     * @param string $newSpaceName
     * @return JSONResponse
     * 
     * @todo Manage errors
     */
    public function renameSpace($workspace, $newSpaceName) {
        
        if (gettype($workspace) === 'object') {
            $workspace = json_decode($workspace, true);
        }

        $errorInSpaceName = $this->checkTheSpaceName($newSpaceName);

        if (!empty($errorInSpaceName)) {
            return new JSONResponse($errorInSpaceName);
        }

		if( $newSpaceName === false ||
			$newSpaceName === null ||
			$newSpaceName === '' 
		) {
			throw new BadRequestException('newSpaceName must be provided');
		}

        $spaceName = $this->deleteBlankSpaceName($newSpaceName);

        $spaceRenamed = $this->spaceService->updateSpaceName($newSpaceName, (int)$workspace['id']);

	    // TODO Handle API call failure (revert space rename and inform user)
        return new JSONResponse([
            'statuscode' => Http::STATUS_NO_CONTENT,
            'space' => $spaceRenamed,
        ]);
    }
}
