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
			throw new \Exception("The $pattern user or email is not exist.", 1);
		}
		return false;
	}
}
