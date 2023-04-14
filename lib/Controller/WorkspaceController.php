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

use OCA\Workspace\BadRequestException;
use OCA\Workspace\CreateGroupException;
use OCA\Workspace\CreateWorkspaceException;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\ManagersWorkspace;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\UserGroup;
use OCA\Workspace\WorkspaceManagerGroup;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class WorkspaceController extends Controller {
	public function __construct(
		IRequest $request,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private SpaceMapper $spaceMapper,
		private SpaceService $spaceService,
		private UserService $userService,
		private WorkspaceCheckService $workspaceCheck,
		private WorkspaceService $workspaceService,
        private UserGroup $userGroup,
        private WorkspaceManagerGroup $workspaceManagerGroup,
		public $AppName
	) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @param string $spaceName it's the space name
	 * @return string whithout the blank to start and end of the space name
	 * @todo move this method
	 */
	private function deleteBlankSpaceName(string $spaceName): string {
		return trim($spaceName);
	}

	/**
	 * @NoAdminRequired
	 * @GeneralManagerRequired
	 * @param string $spaceName
	 * @param int $folderId
	 * @throws BadRequestException
	 * @throws CreateWorkspaceException
	 * @throws CreateGroupException
	 */
	public function createWorkspace(string $spaceName,
		int $folderId): JSONResponse {
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
        $newSpaceManagerGroup = $this->workspaceManagerGroup->create($space);
        $newSpaceUsersGroup = $this->userGroup->create($space);

		// #3 Returns result
		return new JSONResponse([
			'name' => $space->getSpaceName(),
			'id_space' => $space->getId(),
			'folder_id' => $space->getGroupfolderId(),
			'color' => $space->getColorCode(),
			'groups' => GroupFormatter::formatGroups([
                $newSpaceManagerGroup,
                $newSpaceUsersGroup
            ]),
			'statuscode' => Http::STATUS_CREATED,
		]);
	}

  /**
   *
   * Deletes the workspace, and the corresponding groupfolder and groups
   *
   * @NoAdminRequired
   * @SpaceAdminRequired
   * @param array $workspace
   *
   */
	public function destroy(array $workspace): JSONResponse {
        $this->logger->debug('Removing GE users from the WorkspacesManagers group if needed.');
        $GEGroup = $this->groupManager->get(WorkspaceManagerGroup::get($workspace['id']));
        foreach ($GEGroup->getUsers() as $user) {
			$this->userService->removeGEFromWM($user, $workspace);
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
                'name' => $workspace['name'],
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
	 *
	 */
	public function findAll(): JSONResponse {
		$workspaces = $this->workspaceService->getAll();
		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$this->logger->debug('Filtering workspaces');
	    		$filteredWorkspaces = array_values(array_filter($workspaces, function($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace);
			}));
			$workspaces = $filteredWorkspaces;
		}

		return new JSONResponse($workspaces);
	}

	/**
	 * @NoAdminRequired
	 * @param string|array $workspace
	 */
	public function addGroupsInfo(string|array $workspace): JSONResponse {
		return new JSONResponse($this->workspaceService->addGroupsInfo($workspace));
	}

	/**
	 * @NoAdminRequired
	 * @param string|array $workspace
	 */
	public function addUsersInfo(string|array $workspace): JSONResponse {
        if (gettype($workspace) === 'string') {
			$workspace = json_decode($workspace, true);
		}
		return new JSONResponse($this->workspaceService->addUsersInfo($workspace));
	}

	/**
	 * Returns a list of users whose name matches $term
	 *
	 * @NoAdminRequired
	 * @param string $term
	 * @param string $spaceId
	 * @param string|array $space
	 *
	 */
	public function lookupUsers(string $term,
		string $spaceId,
		string|array $space): JSONResponse {
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
	 * @param array|string $space
	 * @param string $userId
	 *
	 */
	public function changeUserRole(array|string $space,
		string $userId): JSONResponse {
		if (gettype($space) === 'string') {
			$space = json_decode($space, true);
		}

		$user = $this->userManager->get($userId);
        $GEgroup = $this->groupManager->get(WorkspaceManagerGroup::get($space['id']));
		if ($GEgroup->inGroup($user)) {
			// Changing a user's role from admin to user
			$GEgroup->removeUser($user);
        		$this->logger->debug('Removing a user from a GE group. Removing it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group if needed.');
			$this->userService->removeGEFromWM($user, $space);
		} else {
			// Changing a user's role from user to admin
			$this->groupManager->get(WorkspaceManagerGroup::get($space['id']))->addUser($user);
			$this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS)->addUser($user);
		}

		return new JSONResponse();
	}

	/**
	 *
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * @param array|string $workspace
	 * @param string $newSpaceName
	 *
	 * @todo Manage errors
	 */
	public function renameSpace(array|string $workspace,
		string $newSpaceName): JSONResponse {
		if (gettype($workspace) === 'string') {
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
}
