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

use Exception;
use OCA\Workspace\BadRequestException;
use OCA\Workspace\CreateGroupException;
use OCA\Workspace\CreateWorkspaceException;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\GroupsWorkspace;
use OCA\Workspace\ManagersWorkspace;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class WorkspaceController extends Controller {
	private IGroupManager $groupManager;
	private IUserManager $userManager;
	private LoggerInterface $logger;
	private SpaceMapper $spaceMapper;
	private SpaceService $spaceService;
	private UserService $userService;
	private WorkspaceCheckService $workspaceCheck;
	private WorkspaceService $workspaceService;

	public function __construct(
		$AppName,
		IGroupManager $groupManager,
		LoggerInterface $logger,
		IRequest $request,
		IUserManager $userManager,
		SpaceMapper $mapper,
		SpaceService $spaceService,
		UserService $userService,
		WorkspaceCheckService $workspaceCheck,
		WorkspaceService $workspaceService
	) {
		parent::__construct($AppName, $request);
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->spaceMapper = $mapper;
		$this->spaceService = $spaceService;
		$this->userManager = $userManager;
		$this->userService = $userService;
		$this->workspaceCheck = $workspaceCheck;
		$this->workspaceService = $workspaceService;
	}

	/**
	 * @param string $spaceName it's the space name
	 * @return string whithout the blank to start and end of the space name
	 * @todo move this method
	 */
	private function deleteBlankSpaceName(string $spaceName) {
		return trim($spaceName);
	}

	/**
	 * @NoAdminRequired
	 * @GeneralManagerRequired
	 * @NoCSRFRequired
	 * @param string $spaceName
	 * @param int $folderId
	 * @throws BadRequestException
	 * @throws CreateWorkspaceException
	 * @throws CreateGroupException
	 */
	public function createWorkspace(string $spaceName, int $folderId) {
		if ($spaceName === false ||
			$spaceName === null ||
			$spaceName === ''
		) {
			throw new BadRequestException('spaceName must be provided');
		}

		$this->workspaceCheck->containSpecialChar($spaceName);
		$this->workspaceCheck->isExist($spaceName);

		$spaceName = $this->deleteBlankSpaceName($spaceName);

		$space = new Space();
		$space->setSpaceName($spaceName);
		$space->setGroupfolderId($folderId);
		$space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
		$this->spaceMapper->insert($space);

		if (is_null($space)) {
			throw new CreateWorkspaceException('Error to create a space.', Http::STATUS_CONFLICT);
		}

		// #2 create groups
		$newSpaceManagerGroup = $this->groupManager->createGroup(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space->getId());

		if (is_null($newSpaceManagerGroup)) {
			throw new CreateGroupException('Error to create a Space Manager group.', Http::STATUS_CONFLICT);
		}

		$newSpaceUsersGroup = $this->groupManager->createGroup(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_USERS . $space->getId());

		if (is_null($newSpaceUsersGroup)) {
			throw new CreateGroupException('Error to create a Space Users group.', Http::STATUS_CONFLICT);
		}

		$newSpaceManagerGroup->setDisplayName(GroupsWorkspace::SPACE_MANAGER . $space->getId());
		$newSpaceUsersGroup->setDisplayName(GroupsWorkspace::SPACE_USERS . $space->getId());

		// #3 Returns result
		return new JSONResponse([
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
    *
    * @NoCSRFRequired
    * @NoAdminRequired
    * @SpaceAdminRequired
    */
    public function removeUsersFromWorkspacesManagers(string $gid, string $spaceId): JSONResponse {
        if (!preg_match(
            '/^'. GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . '[0-9]/',
            $gid))
        {
            throw new Exception('It\'s not a manager group related to workspace'
            . ' (example : ' . GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . '-<ID>)');
        }

        $this->logger->debug('Removing GE users from the WorkspacesManagers group if needed.');
        $managerGroup = $this->groupManager
            ->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $spaceId);

        $users = [];
        foreach ($managerGroup->getUsers() as $user) {
            $users[] = $user->getUID();
            $this->userService->removeGEFromWM($user, $spaceId);
        }

        return new JSONResponse([
            'message' => 'All users are removed of the WorkspacesManagers group.',
            'users' => $users
        ], HTTP::STATUS_OK);
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
			$filteredWorkspaces = array_values(array_filter($workspaces, function ($workspace) {
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
	 * @NoCSRFRequired
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
		$GEgroup = $this->groupManager->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space['id']);

		if ($GEgroup->inGroup($user)) {
			// Changing a user's role from admin to user
			$GEgroup->removeUser($user);
			$this->logger->debug('Removing a user from a GE group. Removing it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group if needed.');
			$this->userService->removeGEFromWM($user, $space['id']);
		} else {
			// Changing a user's role from user to admin
			$this->groupManager->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space['id'])->addUser($user);
			$this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS)->addUser($user);
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

		$this->workspaceCheck->containSpecialChar($newSpaceName);

		if ($newSpaceName === false ||
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

    /**
     * @NoAdminRequired
     * @SpaceAdminRequired
     */
    public function destroyAllGroupsFromAWorkspace(string $spaceId, array $groupsName): JSONResponse
    {
        $groups = [];
		$this->logger->debug('Removing workspaces groups.');
		foreach ($groupsName as $groupName) {
			$groups[] = $groupName;
			$this->groupManager->get($groupName)->delete();
		}

        return new JSONResponse([
            'message' => 'All groups are deleted for a workspace',
            'groups' => $groups
        ], HTTP::STATUS_OK);
    }
}
