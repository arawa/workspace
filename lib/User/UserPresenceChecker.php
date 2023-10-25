<?php

namespace OCA\Workspace\User;

use OCP\IUserManager;

class UserPresenceChecker {
	public function __construct(private IUserManager $userManager,
		private UserSearcher $userSearcher) {
	}

	public function checkUserExist(string $username): bool {
		$user = $this->userSearcher->searchUsers($username)[0];
		if (is_null($user)) {
			throw new \Exception("The $username user is not exist.", 1);
		}
		return false;
	}
}
