<?php

namespace OCA\Workspace\Users;

use OCP\IUserManager;

class UsersExistCheck {
	public function __construct(private IUserManager $userManager) {
	}

	/**
	 * Check if all users exists in a list.
	 *
	 * @param String[] $users - example [ 'users1', 'users2', 'users3' ]
	 */
	public function checkUsersExist(array $users): bool {
		foreach($users as $user) {
			$userNotExist = $this->userManager->userExists($user);
			if(!$userNotExist) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if an user exist or not.
	 */
	public function checkUserExist(string $name): bool {
		return $this->userManager->userExists($name);
	}
}
