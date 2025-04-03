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
			$o_group = $this->groupManager->get($groupName);
			if ($o_group !== null) {
				$users[] = $o_group->getUsers();
			}
		}

		$usersMerged = array_merge([], ...$users);

		return $usersMerged;
	}
}
