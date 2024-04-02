<?php

namespace OCA\Workspace\Share\Group;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;

class ShareMembersOnlyFilter {
	public function __construct(
		private IGroupManager $groupManager,
		private IManager $shareManager,
		private IUserSession $userSession
	) {
	}

	/**
	 * @param IUser[] $users
	 * @return IUser[]
<<<<<<< HEAD
	 * Return users with groups in common.
	 * Except some listed in the drop-down list
	 * (`Settings > Share > Restrict users to only share with users in their groups > Ingore the following groups when cheking group membership`).
=======
     * Return users with groups in common.
     * Except some listed in the drop-down list
     * (`Settings > Share > Restrict users to only share with users in their groups > Ingore the following groups when cheking group membership`).
>>>>>>> fc2d0de (fix(share): Return users with common groups)
	 */
	public function excludeGroupsList(array $users): array {
		$usersNotExclude = [];

		if (method_exists($this->shareManager, "shareWithGroupMembersOnlyExcludeGroupsList")) {
			$excludedGroups = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();
<<<<<<< HEAD
			$userSession = $this->userSession->getUser();
			$groupsOfUserSession = $this->groupManager->getUserGroups($userSession);
			$groupnamesOfUserSession = array_map(fn ($group) => $group->getGID(), $groupsOfUserSession);
=======
		    $userSession = $this->userSession->getUser();
            $groupsOfUserSession = $this->groupManager->getUserGroups($userSession);
            $groupnamesOfUserSession = array_map(fn($group) => $group->getGID(), $groupsOfUserSession);
>>>>>>> fc2d0de (fix(share): Return users with common groups)

			if (!empty($excludedGroups)) {
				$usersNotExclude = array_filter($users, function ($user) use ($excludedGroups, $groupnamesOfUserSession) {
					$groups = $this->groupManager->getUserGroups($user);
					$groupnames = array_values(array_map(fn ($group) => $group->getGID(), $groups));
<<<<<<< HEAD
					$commonGroups = array_intersect($groupnamesOfUserSession, $groupnames);
					return count(array_diff($commonGroups, $excludedGroups)) !== 0;
				});
			}

			return $usersNotExclude;
=======
                    $commonGroups = array_intersect($groupnamesOfUserSession, $groupnames);
                    return count(array_diff($commonGroups, $excludedGroups)) !== 0;
				});
			}

            return $usersNotExclude;
>>>>>>> fc2d0de (fix(share): Return users with common groups)
		}
	}

	/**
	 * @param IUser[] $users
	 *
	 * @return IUser[]
	 */
	public function filterUsersGroupOnly(array $users): array {
		$usersInTheSameGroup = [];
		$userSession = $this->userSession->getUser();
		$groupsOfUserSession = $this->groupManager->getUserGroups($userSession);

		foreach ($groupsOfUserSession as $group) {
			$usersInTheSameGroup = array_merge($usersInTheSameGroup, $group->getUsers());
		}

		$usersInTheSameGroup = array_filter(
			$users,
			function ($user) use ($usersInTheSameGroup) {
				$usernames = array_values(array_map(fn ($user) => $user->getUID(), $usersInTheSameGroup));
				return in_array($user->getUID(), $usernames);
			});

		return $usersInTheSameGroup;
	}
}
