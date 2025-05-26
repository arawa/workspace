<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Space;

use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\CreateWorkspaceException;
use OCA\Workspace\Exceptions\WorkspaceNameExistException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Group\AddedGroups\AddedGroups;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Group\SubGroups\SubGroup;
use OCA\Workspace\Group\User\UserGroup as UserWorkspaceGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\ColorCode;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

class SpaceManager {
	public function __construct(
		private GroupfolderHelper $folderHelper,
		private RootFolder $rootFolder,
		private WorkspaceCheckService $workspaceCheck,
		private UserGroup $userGroup,
		private AdminGroup $adminGroup,
		private AdminUserGroup $adminUserGroup,
		private AddedGroups $addedGroups,
		private SubGroup $subGroup,
		private UserWorkspaceGroup $userWorkspaceGroup,
		private SpaceMapper $spaceMapper,
		private ConnectedGroupsService $connectedGroupsService,
		private LoggerInterface $logger,
		private UserFormatter $userFormatter,
		private UserService $userService,
		private IGroupManager $groupManager,
		private WorkspaceManagerGroup $workspaceManagerGroup,
		private WorkspaceService $workspaceService,
		private ColorCode $colorCode,
	) {
	}
	
	public function create(string $spacename): array {
		if ($spacename === false ||
			$spacename === null ||
			$spacename === ''
		) {
			throw new BadRequestException('Error creating workspace', 'spaceName must be provided');
		}

		if ($this->workspaceCheck->containSpecialChar($spacename)) {
			throw new BadRequestException('Error creating workspace', 'Your Workspace name must not contain the following characters: ' . implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL)));
		}
		
		if ($this->workspaceCheck->isExist($spacename)) {
			throw new WorkspaceNameExistException(
				title: 'Error - Duplicate space name',
				message: "This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist."
			);
		}

		$spacename = $this->deleteBlankSpaceName($spacename);

		$folderId = $this->folderHelper->createFolder($spacename);

		$space = new Space();
		$space->setSpaceName($spacename);
		$space->setGroupfolderId($folderId);
		$space->setColorCode($this->colorCode->generate());
		$this->spaceMapper->insert($space);


		if (is_null($space)) {
			throw new CreateWorkspaceException('Error to create a space.', Http::STATUS_CONFLICT);
		}

		$newSpaceManagerGroup = $this->workspaceManagerGroup->create($space);
		$newSpaceUsersGroup = $this->userGroup->create($space);

		$this->folderHelper->setFolderAcl($folderId, true);

		$this->folderHelper->addApplicableGroup(
			$folderId,
			$newSpaceManagerGroup->getGID(),
		);

		$this->folderHelper->addApplicableGroup(
			$folderId,
			$newSpaceUsersGroup->getGID(),
		);

		$this->folderHelper->setManageACL(
			$folderId,
			'group',
			$newSpaceManagerGroup->getGID(),
			true
		);

		$groupfolder = $this->folderHelper->getFolder(
			$folderId,
			$this->rootFolder->getRootFolderStorageId()
		);

		return [
			'name' => $space->getSpaceName(),
			'id' => $space->getId(),
			'id_space' => $space->getId(),
			'folder_id' => $space->getGroupfolderId(),
			'color' => $space->getColorCode(),
			'groups' => GroupFormatter::formatGroups([
				$newSpaceManagerGroup,
				$newSpaceUsersGroup
			]),
			'added_groups' => (object)[],
			'quota' => $groupfolder['quota'],
			'size' => $groupfolder['size'],
			'acl' => $groupfolder['acl'],
			'manage' => $groupfolder['manage'],
			'userCount' => 0
		];
	}

	/**
	 * @return array<{
	 * 	id: int,
	 * 	mount_point: string,
	 * 	groups: array,
	 * 	quota: int,
	 * 	size: int,
	 * 	acl: bool,
	 *  manage: array<Object>
	 * 	groupfolder_id: int,
	 * 	name: string,
	 * 	color_code: string,
	 *  userCount: int,
	 *  users: array<Object>
	 *  added_groups: array<Object>
	 * }
	 *
	 * It's a function for WorkspaceApiOcsController
	 */
	public function find(int $id): array {
		$space = $this->spaceMapper->find($id);

		if (is_null($space)) {
			$this->logger->error("The workspace with the id {$id} is not found.");
			throw new OCSNotFoundException("The workspace with the id {$id} is not found.");
		}

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false || is_null($groupfolder)) {
			$folderId = $space->getGroupfolderId();
			$this->logger->error("Failed loading groupfolder with the folderId {$folderId}");
			throw new OCSNotFoundException("Failed loading groupfolder with the folderId {$folderId}");
		}

		$space = array_merge($groupfolder, $space->jsonSerialize());

		$gids = array_keys($space['groups']) ?? [];
		$wsGroups = [];
		$addedGroups = [];

		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);

			if (UserGroup::isWorkspaceGroup($group)) {
				$wsGroups[] = $group;
			} else {
				$addedGroups[] = $group;
			}

			if (UserGroup::isWorkspaceUserGroupId($gid)) {
				$space['userCount'] = $group->count();
			}
		}

		$users = $this->workspaceService->addUsersInfo($space);
		$space['users'] = $users;

		$space['groups'] = GroupFormatter::formatGroups($wsGroups);
		$space['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

		return $space;
	}
	
	public function get(int $spaceId): array {

		$space = $this->spaceMapper->find($spaceId);
		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());
		if ($groupfolder === false) {
			return [];
		}

		$workspace = array_merge($space->jsonSerialize(), $groupfolder);
		$workspace['id'] = $space->getSpaceId();

		$folderInfo = $this->folderHelper->getFolder(
			$workspace['groupfolder_id'],
			$this->rootFolder->getRootFolderStorageId()
		);
		$workspace = ($folderInfo !== false) ? array_merge(
			$folderInfo,
			$workspace
		) : $workspace;

		$wsGroups = [];
		$addedGroups = [];
		$gids = array_keys($workspace['groups'] ?? []);
		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);
			if (is_null($group)) {
				continue;
			}
			if (UserGroup::isWorkspaceGroup($group)) {
				$wsGroups[] = $group;
			} else {
				$addedGroups[] = $group;
			}

			if (UserGroup::isWorkspaceUserGroupId($gid)) {
				$workspace['userCount'] = $group->count();
			}
		}

		$workspace['users'] = $this->adminGroup->getUsersFormatted($folderInfo, $space);
		$workspace['groups'] = GroupFormatter::formatGroups($wsGroups);
		$workspace['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

		return $workspace;
	}

	public function getByName(string $spacename): array {
		$space = $this->spaceMapper->findByName($spacename);
		if ($space === null) {
			return [];
		}
		return $this->get($space->getSpaceId());
	}

	public function attachGroup(int $folderId, string $gid): void {
		$this->folderHelper->addApplicableGroup($folderId, $gid);
	}

	/**
	 * @param string $spaceName it's the space name
	 * @return string whithout the blank to start and end of the space name
	 * @todo move this method
	 */
	private function deleteBlankSpaceName(string $spaceName): string {
		return trim($spaceName);
	}

	public function remove(string $spaceId): void {
		$space = $this->get($spaceId);

		foreach ($this->adminGroup->getUsers($spaceId) as $user) {
			if ($this->userService->canRemoveWorkspaceManagers($user)) {
				$this->logger->debug('Remove user ' . $user->getUID() . ' from the Workspace Manager group in ' . $space['name']);
				$this->adminUserGroup->removeUser($user);
			}
		}

		$this->logger->debug('Removing workspace groups.');
		foreach (array_keys($space['groups']) as $group) {
			$o_group = $this->groupManager->get($group);
			if ($o_group !== null) {
				$o_group->delete();
			}
		}

		$folderId = $space['groupfolder_id'];
		$this->folderHelper->removeFolder($folderId);
	}

	/**
	 * @param int $spaceId related to the id of a space.
	 * @param string $newSpaceName related to the  new space name.
	 */
	public function rename(int $spaceId, string $newSpaceName): void {
		$space = $this->get($spaceId);

		if ($this->workspaceCheck->isExist($newSpaceName)) {
			throw new WorkspaceNameExistException(
				title: 'Error - Duplicate space name',
				message: "This space or groupfolder already exist. Please, input another space.\nIf \"toto\" space exist, you cannot create the \"tOTo\" space.\nMake sure you the groupfolder doesn't exist."
			);
		}

		$this->folderHelper->renameFolder($space['groupfolder_id'], $newSpaceName);
		$this->spaceMapper->updateSpaceName($newSpaceName, $spaceId);
	}
}
