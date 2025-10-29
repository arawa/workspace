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

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\WorkspaceNameSpecialCharException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
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
		private AdminGroup $adminGroup,
		private AdminUserGroup $adminUserGroup,
		private GroupfolderHelper $folderHelper,
		private IGroupManager $groupManager,
		private RootFolder $rootFolder,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private SpaceMapper $spaceMapper,
		private SpaceService $spaceService,
		private UserService $userService,
		private ConnectedGroupsService $connectedGroups,
		private WorkspaceCheckService $workspaceCheck,
		private WorkspaceService $workspaceService,
		private UserGroup $userGroup,
		private UserFormatter $userFormatter,
		private WorkspaceManagerGroup $workspaceManagerGroup,
		private SpaceManager $spaceManager,
		public $AppName,
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
	 */
	public function createWorkspace(string $spaceName): JSONResponse {
		try {
			$workspace = $this->spaceManager->create($spaceName);
		} catch (\Exception $e) {
			if ($e instanceof WorkspaceNameSpecialCharException) {
				$specialChars = implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));
				throw new BadRequestException(
					title: 'Error creating workspace',
					message: 'Your Workspace name must not contain the following characters: {specialChars}',
					argsMessage: [ 'specialChars' => $specialChars ]
				);
			}
		}

		return new JSONResponse(
			array_merge(
				$workspace,
				[ 'statuscode' => Http::STATUS_CREATED ]
			)
		)
		;
	}

	/**
	 *
	 * Deletes the workspace, and the corresponding groupfolder and groups
	 *
	 * @NoAdminRequired
	 * @GeneralManagerRequired
	 * @param int $spaceId
	 *
	 */
	public function destroy(int $spaceId): JSONResponse {
		$space = $this->spaceManager->get($spaceId);
		$groups = [];
		foreach (array_keys($space['groups']) as $group) {
			$groups[] = $group;
		}

		$this->spaceManager->remove($spaceId);

		return new JSONResponse([
			'http' => [
				'statuscode' => 200,
				'message' => 'The space is deleted.'
			],
			'data' => [
				'name' => $space['name'],
				'groups' => $groups,
				'space_id' => $space['id'],
				'groupfolder_id' => $space['groupfolderId'],
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
		$spaces = [];
		foreach ($workspaces as $workspace) {
			$folderInfo = $this->folderHelper->getFolder(
				$workspace['groupfolder_id'],
				$this->rootFolder->getRootFolderStorageId()
			);
			$space = ($folderInfo !== false) ? array_merge(
				$folderInfo,
				$workspace
			) : $workspace;

			$gids = array_keys($space['groups'] ?? []);
			$wsGroups = [];
			$space['users'] = (object)[];
			$addedGroups = [];

			foreach ($gids as $gid) {
				$group = $this->groupManager->get($gid);
				if (is_null($group)) {
					$this->logger->warning(
						"Be careful, the $gid group does not exist in the oc_groups table."
						. ' The group is still present in the oc_group_folders_groups table.'
						. ' To fix this inconsistency, recreate the group using occ commands.'
					);
					continue;
				}
				if (UserGroup::isWorkspaceGroup($group)) {
					$wsGroups[] = $group;
				} else {
					$addedGroups[] = $group;
				}

				if (UserGroup::isWorkspaceUserGroupId($gid)) {
					$space['usersCount'] = $group->count();
				}
			}

			$space['groups'] = GroupFormatter::formatGroups($wsGroups);
			$space['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

			$spaces[] = $space;
		}
		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$this->logger->debug('Filtering workspaces');
			$filteredWorkspaces = array_values(array_filter($spaces, function ($space) {
				return $this->userService->isSpaceManagerOfSpace($space);
			}));
			$spaces = $filteredWorkspaces;
		}

		return new JSONResponse($spaces);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getUsers(int $spaceId): JSONResponse {

		$space = $this->spaceMapper->find($spaceId);

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false) {
			return new JSONResponse(
				[
					'message' => 'Failed loading groupfolder ' . $space->getGroupfolderId(),
					'success' => false
				],
				Http::STATUS_BAD_REQUEST);
		}

		$workspace = array_merge($groupfolder, $space->jsonSerialize());
		$users = $this->workspaceService->addUsersInfo($workspace);

		return new JSONResponse($users);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getAdmins(int $spaceId): JSONResponse {

		$space = $this->spaceMapper->find($spaceId);

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false) {
			return new JSONResponse(
				[
					'message' => 'Failed loading groupfolder ' . $space->getGroupfolderId(),
					'success' => false
				],
				Http::STATUS_BAD_REQUEST);
		}

		$adminUsers = [];
		foreach ($groupfolder['groups'] as $gid => $groupInfo) {
			if (WorkspaceManagerGroup::isWorkspaceAdminGroupId($gid)) {
				$group = $this->groupManager->get($gid);
				if ($group !== null) {
					$users = $group->getUsers();
					$adminUsers = $this->userFormatter->formatUsers($users, $groupfolder, (string)$spaceId);
				}
				break;
			}
		}

		return new JSONResponse($adminUsers);
	}

	/**
	 * @NoAdminRequired
	 * @GeneralManagerRequired
	 * @param int $spaceId of workspace
	 * @param int $quota in bytes
	 */
	public function updateQuota(int $spaceId, int $quota): JSONResponse {

		$space = $this->spaceMapper->find($spaceId);

		if (is_null($space)) {
			throw new \Exception('Workspace does not exist.');
		}

		if (!is_int($quota)) {
			throw new BadRequestException('Error setting quota', 'The quota parameter is not an integer.');
		}

		$space = $this->spaceMapper->find($spaceId);
		$this->folderHelper->setFolderQuota($space->getGroupfolderId(), $quota);

		return new JSONResponse([
			'quota' => $quota,
			'success' => true
		]);
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
			if ($this->userService->canRemoveWorkspaceManagers($user)) {
				$this->userService->removeGEFromWM($user, $space);
			}
			// Changing a user's role from admin to user
			$GEgroup->removeUser($user);
			$this->logger->debug('Removing a user from a GE group. Removing it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group if needed.');
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
	 * @param int $spaceId
	 * @param string $newSpaceName
	 *
	 */
	public function renameSpace(int $spaceId,
		string $newSpaceName): JSONResponse {

		if ($this->workspaceCheck->containSpecialChar($newSpaceName)) {
			throw new BadRequestException('Error to rename the workspace', 'Your Workspace name must not contain the following characters: {args}', argsMessage: [implode(' ', array_unique(str_split(WorkspaceCheckService::CHARACTERS_SPECIAL)))]);
		}

		if ($newSpaceName === false
			|| $newSpaceName === null
			|| $newSpaceName === ''
		) {
			throw new BadRequestException('Error to rename the workspace', 'newSpaceName must be provided');
		}

		$spaceName = $this->deleteBlankSpaceName($newSpaceName);

		$this->spaceManager->rename($spaceId, $spaceName);

		return new JSONResponse([
			'statuscode' => Http::STATUS_NO_CONTENT,
			'space' => $spaceName,
		]);
	}
}
