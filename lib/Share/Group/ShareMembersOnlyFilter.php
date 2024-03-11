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
		 */
	public function excludeGroupsList(array $users): array {
		$usersNotExclude = [];

		if (method_exists($this->shareManager, "shareWithGroupMembersOnlyExcludeGroupsList")) {
			$excludedGroups = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();

			if (!empty($excludedGroups)) {
				$usersNotExclude = array_filter($users, function ($user) use ($excludedGroups) {
					$groups = $this->groupManager->getUserGroups($user);
					$groupnames = array_values(array_map(fn ($group) => $group->getGID(), $groups));
					$diff = array_diff($excludedGroups, $groupnames);
					return count($diff) !== 0;
				});

				return $usersNotExclude;
			}
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
