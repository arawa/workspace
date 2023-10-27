<?php

namespace OCA\Workspace\User;

use OCP\IUserManager;

class UserPresenceChecker {
	public function __construct(private IUserManager $userManager,
		private UserFinder $userFinder) {
	}

	public function checkUserExist(string $pattern): bool {
		$user = $this->userFinder->findUser($pattern);
		if (is_null($user)) {
			return false;
		}
		return true;
	}
}
