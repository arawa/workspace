<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2022 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Service\Group;

use OCA\Workspace\Exceptions\GroupException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;

class GroupsWorkspaceService {
	public function __construct(
		private IGroupManager $groupManager,
		private WorkspaceManagerGroup $workspaceManagerGroup,
		private UserGroup $userGroup,
	) {
	}

	/**
	 * @throws GroupException
	 */
	public function getWorkspaceManagerGroup(string $spaceId): IGroup {
		$groupSpaceManager = $this->groupManager->get(
			WorkspaceManagerGroup::get($spaceId)
		);

		if (is_null($groupSpaceManager)) {
			throw new GroupException('Error to get the workspace manage group relative to workspace.');
		}

		return $groupSpaceManager;
	}

	/**
	 * @throws GroupException
	 */
	public function getUserGroup(string $spaceId): IGroup {
		$groupUser = $this->groupManager->get(
			UserGroup::get($spaceId)
		);

		if (is_null($groupUser)) {
			throw new GroupException('Error to get the workspace manage group relative to workspace.');
		}

		return $groupUser;
	}

	/**
	 * @return String[]
	 */
	public function getGroupsUserFromGroupfolder(IUser $user, array $groupfolder, string $spaceId): array {
		$groupsWorkspace = [
			$this->getWorkspaceManagerGroup($spaceId)->getGID(),
			$this->getUserGroup($spaceId)->getGID()
		];
		$groups = [];
		foreach ($this->groupManager->getUserGroups($user) as $group) {
			if (
				in_array($group->getGID(), array_keys($groupfolder['groups']))
				|| in_array($group->getGID(), $groupsWorkspace)
			) {
				array_push($groups, $group->getGID());
			}
		}

		return $groups;
	}

	/**
	 * @param IUser[] $users
	 */
	public function transferUsersToGroup(array $users, IGroup $group): void {
		if (is_null($group)) {
			throw new GroupException('Error parameter, $group is null.');
		}

		foreach ($users as $user) {
			$group->addUser($user);
		}
	}

	public function removeGroup(IGroup $group): void {
		$gid = $group->getGID();

		if (str_starts_with($this->userGroup->getPrefix(), $gid) || str_starts_with($this->workspaceManagerGroup->getPrefix(), $gid)) {
			throw new GroupException("You cannot remove the user group (U-) or the workspace manager group (WM-) as they are essential for the system's functionality.");
		}

		if ($gid === ManagersWorkspace::GENERAL_MANAGER) {
			throw new GroupException('You cannot remove the GeneralManager group.');
		}

		if ($gid === ManagersWorkspace::WORKSPACES_MANAGERS) {
			throw new GroupException('You cannot remove the WorkspacesManagers group.');
		}

		$group->delete();
	}
}
