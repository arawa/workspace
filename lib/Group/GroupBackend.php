<?php

/**
 * @copyright Copyright (c) 2024 Arawa
 *
 * @author 2024 Sébastien Marinier <seb@smarinier.net>
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

namespace OCA\Workspace\Group;

use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\User\Backend\UserBackend;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\GroupInterface;
use OCP\IGroupManager;
use OCP\IUserManager;

class GroupBackend extends ABackend implements GroupInterface, INamedBackend, ICountUsersBackend {

	private bool $avoidRecurse_users;
	private bool $avoidRecurse_groups;

	/**
	 * @param IGroupManager $groupManager parent group manager
	 */
	public function __construct(
		protected IGroupManager $groupManager,
		protected IUserManager $userManager,
		private ConnectedGroupsService $connectedGroups,
	) {
		$this->avoidRecurse_users = $this->avoidRecurse_groups = false;
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		// not backend responsability
		return false;
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return string[] an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		if ($this->avoidRecurse_groups) {
			return [];
		}

		$avoid = $this->avoidRecurse_groups;
		$this->avoidRecurse_groups = true;
		$user = $this->userManager->get($uid);

		if ($user) {
			$groupIds = $this->groupManager->getUserGroupIds($user);
		} else {
			$groupIds = [];
		}

		if (empty($groupIds)) {
			if (str_starts_with($uid, 'SPACE-UWS-')) {
				// die;
				preg_match('/[0-9].*/', $uid, $matches);
				$id = $matches[0];
				return [ "SPACE-U-{$id}", "SPACE-GE-{$id}"];
			}
		}


		$this->avoidRecurse_groups = $avoid;
		if (empty($groupIds)) {
			return [];
		}
		$userGroups = [];
		foreach ($groupIds as $gid) {
			$connectedGids = $this->connectedGroups->getConnectedSpaceToGroupIds($gid);
			if ($connectedGids !== null && $user->isEnabled()) {
				$userGroups = array_merge($userGroups, $connectedGids);
			}
		}

		if (str_starts_with($uid, 'SPACE-UWS-')) {
			$userManagerWorkspaces = array_filter($groupIds, fn ($gid) => str_starts_with($gid, 'SPACE-GE-'));
			$userWorkspaces = array_filter($groupIds, fn ($gid) => str_starts_with($gid, 'SPACE-U-'));

			foreach ($userManagerWorkspaces as $gid) {
            	$userGroups[] = $gid;
			}

			foreach ($userWorkspaces as $gid) {
            	$userGroups[] = $gid;
			}
		}

		return $userGroups;
	}

	/**
	 * get a list of all groups
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return []; // return all virtual groups
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		// @note : need to implement, but this backend doesn't manage existence of connected groups
		return $this->connectedGroups->hasConnectedGroups($gid);
	}

	/**
	 * get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return string[] an array of user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if ($this->avoidRecurse_users) {
			return [];
		}

		$groups = $this->connectedGroups->getConnectedGroupsToSpaceGroup($gid);
		if ($groups === null) {
			return [];
		}

		$users = [];
		$avoid = $this->avoidRecurse_users;
		$this->avoidRecurse_users = true;
		foreach ($groups as $group) {
			if (!is_null($group)) {
				foreach ($group->getUsers() as $user) {
					if ($user->isEnabled()) {
						$users[] = $user->getUID();
					}
				};
			}
		}
		$this->avoidRecurse_users = $avoid;

		if (
			str_starts_with($gid, 'SPACE-U-')
			|| str_starts_with($gid, 'SPACE-GE-')
		) {
			preg_match('/[0-9].*/', $gid, $matches);
			$id = $matches[0];
			$users[] = "SPACE-UWS-{$id}";
		}

		return $users;
	}

	public function getBackendName(): string {
		return 'WorkspaceGroupBackend';
	}


	public function countUsersInGroup(string $gid, string $search = ''): int {

		$users = $this->usersInGroup($gid);
		if (!is_array($users)) {
			return 0;
		}

		// get database users first
		$group = $this->groupManager->get($gid);
		$this->avoidRecurse_users = true;
		$usersDb = $group->getUsers();
		$this->avoidRecurse_users = false;

		$nbUsers = 0;
		foreach ($users as $userId) {
			if (!isset($usersDb[$userId])) {
				$usersDb[$userId] = true;
				$nbUsers ++;
			}
		}
		return $nbUsers;
	}
};
