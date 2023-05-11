<?php

namespace OCA\Workspace\Service\User;

use OCP\IGroupManager;
use OCP\IUser;

class UserWorkspace {

    public function __construct(
		private IGroupManager $groupManager
	) {
	}

	/**
	 * @param String[] $groupsName
	 * @return IUser[]
	 */
	public function getUsersFromGroup(array $groupsName): array {
		$users = [];

		foreach ($groupsName as $groupName) {
			$users[] = $this->groupManager->get($groupName)->getUsers();
		}

		$usersMerged = array_merge([], ...$users);

		return $usersMerged;
	}
}
