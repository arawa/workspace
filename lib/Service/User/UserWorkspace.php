<?php

namespace OCA\Workspace\Service\User;

use OCP\IGroupManager;
use OCP\IUser;

class UserWorkspace
{
	private IGroupManager $groupManager;

	public function __construct(
		IGroupManager $groupManager
	)
	{
		$this->groupManager = $groupManager;
	}

	/**
	 * @param String[] $groupsName
	 * @return IUser[]
	 */
	public function getUsersFromGroup($groupsName)
	{

		$users = [];

		foreach ($groupsName as $groupName) {
            $users[] = $this->groupManager->get($groupName)->getUsers();
		}

		$usersMerged = array_merge([], ...$users);

		return $usersMerged;
	}
}
