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
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Exceptions\SpacenameExistException;
use OCA\Workspace\Exceptions\WorkspaceNameSpecialCharException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Group\AddedGroups\AddedGroups;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Group\SubGroups\SubGroup;
use OCA\Workspace\Group\User\UserGroup as UserWorkspaceGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Helper\MountProviderHelper;
use OCA\Workspace\Service\ColorCode;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
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
		private MountProviderHelper $mountProviderHelper,
		private SubGroup $subGroup,
		private IUserManager $userManager,
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
		if ($spacename === false
			|| $spacename === null
			|| $spacename === ''
		) {
			throw new BadRequestException('Error creating workspace', 'spaceName must be provided');
		}

		if ($this->workspaceCheck->containSpecialChar($spacename)) {
			throw new WorkspaceNameSpecialCharException();
		}

		if ($this->workspaceCheck->isExist($spacename)) {
			throw new SpacenameExistException();
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
			'usersCount' => 0
		];
	}

	public function findGroupsBySpaceId(int $id): array {
		$space = $this->spaceMapper->find($id);
		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		$gids = array_keys($groupfolder['groups']);
		$groups = array_map(fn ($gid) => $this->groupManager->get($gid), $gids);
		$groupsFormatted = GroupFormatter::formatGroups($groups);

		return $groupsFormatted;
	}

	/**
	 * Create a subgroup to a workspace and attaches it in.
	 * @param int $id is the space id.
	 * @return IGroup is the group created.
	 */
	public function createSubgroup(int $id, string $groupname): IGroup {
		$space = $this->spaceMapper->find($id);

		$spacename = $space->getSpaceName();
		$group = $this->subGroup->create($groupname, $id, $spacename);

		$this->folderHelper->addApplicableGroup($space->getGroupfolderId(), $group->getGID());

		$gid = $group->getGID();

		$this->logger->info("The subgroup {$gid} is created within the workspace {$spacename} ({$id})");

		return $group;
	}

	/**
	 *  @return array<{
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
	 *  usersCount: int,
	 *  users: array<Object>
	 *  added_groups: array<Object>
	 * }
	 */
	public function get(int $spaceId): ?array {

		$space = $this->spaceMapper->find($spaceId);

		if (is_null($space)) {
			return null;
		}

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false || is_null($groupfolder)) {
			$folderId = $space->getGroupfolderId();
			$this->logger->error("Failed loading groupfolder with the folderId {$folderId}");
			throw new NotFoundException("Failed loading groupfolder with the folderId {$folderId}");
		}

		$workspace = array_merge($space->jsonSerialize(), $groupfolder);
		$workspace['id'] = $space->getSpaceId();

		$workspace = ($groupfolder !== false) ? array_merge(
			$groupfolder,
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
				$workspace['usersCount'] = $group->count();
			}
		}

		$workspace['users'] = $this->adminGroup->getUsersFormatted($groupfolder, $space);
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

	public function addUsersInWorkspace(int $id, array $uids): void {
		$types = array_unique(array_map(fn ($uid) => gettype($uid), $uids));
		$othersStringTypes = array_values(array_filter($types, fn ($type) => $type !== 'string'));

		if (!empty($othersStringTypes)) {
			throw new BadRequestException('Error in parameters', 'uids params must contain a string array only');
		}

		$usersNotExist = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotExist[] = $uid;
			}
		}

		if (!empty($usersNotExist)) {
			$formattedUsers = implode(array_map(fn ($user) => "- {$user}" . PHP_EOL, $usersNotExist));
			$this->logger->error('These users not exist in your Nextcloud instance : ' . PHP_EOL . $formattedUsers);
			throw new NotFoundException('These users not exist in your Nextcloud instance : ' . PHP_EOL . $formattedUsers);
		}

		$gid = UserGroup::get($id);
		$userGroup = $this->groupManager->get($gid);

		if (is_null($userGroup)) {
			$this->logger->error("The group with {$gid} group doesn't exist.");
			throw new NotFoundException("The group with {$gid} group doesn't exist.");
		}

		$users = array_map(fn ($uid) => $this->userManager->get($uid), $uids);

		foreach ($users as $user) {
			$userGroup->addUser($user);
		}
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

		$this->spaceMapper->deleteSpace($spaceId);

		$folderId = $space['groupfolder_id'];
		$folder = $this->mountProviderHelper->getFolder($folderId);
		$folder->delete();
		$this->folderHelper->removeFolder($folderId);
	}

	/**
	 * @param int $spaceId related to the id of a space.
	 * @param string $newSpaceName related to the  new space name.
	 */
	public function rename(int $spaceId, string $newSpaceName): void {
		$space = $this->get($spaceId);
		$newSpaceName = $this->deleteBlankSpaceName($newSpaceName);

		if ($this->workspaceCheck->containSpecialChar($newSpaceName)) {
			throw new WorkspaceNameSpecialCharException();
		}

		if ($this->workspaceCheck->isExist($newSpaceName)) {
			throw new SpacenameExistException();
		}

		$this->folderHelper->renameFolder($space['groupfolder_id'], $newSpaceName);
		$this->spaceMapper->updateSpaceName($newSpaceName, $spaceId);
	}

	public function setQuota(int $spaceId, int $quota): void {
		$space = $this->spaceMapper->find($spaceId);

		if (is_null($space)) {
			throw new \Exception('Workspace does not exist.');
		}

		if (!is_int($quota)) {
			throw new BadRequestException('Error setting quota', 'The quota parameter is not an integer.');
		}

		$space = $this->spaceMapper->find($spaceId);
		$this->folderHelper->setFolderQuota($space->getGroupfolderId(), $quota);
	}

	public function setColor(int $spaceId, string $colorCode): Space {
		return $this->spaceMapper->updateColorCode($colorCode, $spaceId);
	}

	public function renameGroups(int $spaceId, string $oldSpacename, string $newSpacename): void {
		$space = $this->spaceMapper->find($spaceId);

		if (is_null($space)) {
			return;
		}

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false || is_null($groupfolder)) {
			$folderId = $space->getGroupfolderId();
			$this->logger->error("Failed loading groupfolder with the folderId {$folderId}");
			throw new NotFoundException("Failed loading groupfolder with the folderId {$folderId}");
		}

		$gids = array_values(array_keys($groupfolder['groups']));

		$groupsNotFound = [];
		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);
			if (is_null($group)) {
				$groupsNotFound[] = $gid;
			}
		}

		if (!empty($groupsNotFound)) {
			$gidsStringify = implode(', ', $groupsNotFound);

			throw new \Exception("These groups are not present in your Nextcloud instance : {$gidsStringify}");
		}

		$groups = array_map(fn ($gid) => $this->groupManager->get($gid), $gids);
		$groups = array_filter($groups, fn ($group) => !in_array('LDAP', $group->getBackendNames()));

		foreach ($groups as $group) {
			$newGroupName = str_replace($oldSpacename, $newSpacename, $group->getDisplayName());
			$group->setDisplayName($newGroupName);
		}
	}

	public function removeUsersFromWorkspace(int $id, array $uids): void {
		$types = array_unique(array_map(fn ($uid) => gettype($uid), $uids));
		$othersStringTypes = array_values(array_filter($types, fn ($type) => $type !== 'string'));
		$space = $this->get($id);
		$gids = array_keys($space['groups']);

		if (!empty($othersStringTypes)) {
			throw new OCSBadRequestException('uids params must contain a string array only');
		}

		$usersNotExist = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotExist[] = $uid;
			}
		}

		if (!empty($usersNotExist)) {
			$formattedUsers = implode(array_map(fn ($user) => "- {$user}" . PHP_EOL, $usersNotExist));
			$this->logger->error('These users not exist in your Nextcloud instance : ' . PHP_EOL . $formattedUsers);
			throw new OCSBadRequestException('These users not exist in your Nextcloud instance : ' . PHP_EOL . $formattedUsers);
		}

		$managerGid = WorkspaceManagerGroup::get($id);
		$managerGroup = $this->groupManager->get($managerGid);

		$users = array_map(fn ($uid) => $this->userManager->get($uid), $uids);

		foreach ($users as $user) {
			$uid = $user->getUID();

			if ($managerGroup->inGroup($user)) {
				if ($this->userService->canRemoveWorkspaceManagers($user)) {
					$this->userService->removeGEFromWM($user);
				}
			}

			foreach ($gids as $gid) {
				$group = $this->groupManager->get($gid);
				$group->removeUser($user);
			}

			$managerGroup->removeUser($user);
		}
	}

	/**
	 * @var array<IUser> $users
	 */
	public function removeUsersFromSubGroup(IGroup $group, array $users): void {
		foreach ($users as $user) {
			$group->removeUser($user);
		}
	}

	public function removeUsersFromUserGroup(array $space, IGroup $group, array $users): void {
		$managerGroupGid = "SPACE-GE-{$space['id']}";
		$managerGroup = $this->groupManager->get($managerGroupGid);

		if (is_null($managerGroupGid)) {
			throw new OCSNotFoundException("Impossible to found the group {$managerGroupGid}. You have this error because you want to remove users from the group {$group->getGID()}");
		}

		$gids = array_keys($space['groups']);
		$gids = array_filter($gids, fn ($gid) => $gid !== $managerGroupGid);

		$groups = array_map(fn ($gid) => $this->groupManager->get($gid), $gids);

		foreach ($groups as $group) {
			foreach ($users as $user) {
				$group->removeUser($user);
			}
		}

		$this->removeUsersFromWorkspaceManagerGroup($managerGroup, $users);
	}

	public function removeUsersFromWorkspaceManagerGroup(IGroup $group, array $users): void {
		foreach ($users as $user) {
			if ($group->inGroup($user)) {
				if ($this->userService->canRemoveWorkspaceManagers($user)) {
					$this->userService->removeGEFromWM($user);
				}
			}

			$group->removeUser($user);
		}
	}

	public function addUserAsWorkspaceManager(int $spaceId, string $uid): void {
		$user = $this->userManager->get($uid);
		$managerGid = WorkspaceManagerGroup::get($spaceId);
		$userGid = UserGroup::get($spaceId);

		$managerGroup = $this->groupManager->get($managerGid);
		$userGroup = $this->groupManager->get($userGid);

		if (!$userGroup->inGroup($user)) {
			$userGroup->addUser($user);
		}

		$managerGroup->addUser($user);
		$this->adminGroup->addUser($user, $spaceId);
	}

	public function findAll(): ?array {
		$workspaces = $this->workspaceService->getAll();

		if (empty($workspaces)) {
			return null;
		}

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

		return $spaces;
	}

	/**
	 * @var array<IUser> $users
	 */
	public function addUsersToGroup(IGroup $group, array $users): void {
		foreach ($users as $user) {
			$group->addUser($user);
		}
	}

	/**
	 * @var array<IUser> $users
	 */
	public function addUsersToSubGroup(array $space, IGroup $group, array $users): void {
		$userGroupGid = "SPACE-U-{$space['id']}";
		$userGroup = $this->groupManager->get($userGroupGid);

		if (is_null($userGroup)) {
			throw new NotFoundException("Impossible to found the group {$userGroupGid}. You have this error because you want to remove users from the group {$group->getGID()}");
		}

		foreach ($users as $user) {
			$group->addUser($user);
			$userGroup->addUser($user);
		}
	}

	/**
	 * @var array<IUser> $users
	 */
	public function addUsersToWorkspaceManagerGroup(array $space, IGroup $group, array $users): void {
		$userGroupGid = "SPACE-U-{$space['id']}";
		$userGroup = $this->groupManager->get($userGroupGid);
		$workspaceManagerGroup = $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS);

		if (is_null($userGroup)) {
			throw new NotFoundException("Impossible to found the group {$userGroupGid}. You have this error because you want to remove users from the group {$group->getGID()}");
		}

		foreach ($users as $user) {
			$group->addUser($user);
			$workspaceManagerGroup->addUser($user);
			$userGroup->addUser($user);
		}
	}

	/**
	 * @param int $id represent the space id
	 * @return array{
	 *			uid: string,
	 *			name: string,
	 *			email: string,
	 *			subtitle: string,
	 *			groups: array,
	 *			is_connected: bool,
	 *			profile: string,
	 *			role: string
	 *	}
	 */
	public function findUsersById(int $id): array {
		$space = $this->spaceMapper->find($id);
		$userGroupGid = $this->userGroup->get($id);
		$group = $this->groupManager->get($userGroupGid);

		if (is_null($group)) {
			throw new NotFoundException("The user group {$userGroupGid} is not found");
		}

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());
		if ($groupfolder === false) {
			throw new NotFoundException("The groupfolder {$space->getGroupfolderId()} is not found");
		}

		$workspace = array_merge($groupfolder, $space->jsonSerialize());
		$users = $group->getUsers();

		$users = $this->userFormatter->formatUsers($users, $workspace, (string)$id);

		return $users;
	}
}
