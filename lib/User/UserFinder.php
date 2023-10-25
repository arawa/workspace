<?php

namespace OCA\Workspace\User;

use OCP\IUser;
use OCP\IUserManager;

class UserFinder
{
    public function __construct(private IUserManager $userManager)
    {
    }

    	/**
	 * @param string $email
	 */
	public function findByEmail(string $email): ?IUser {
		$users = $this->userManager->getByEmail($email);
        return $users[0];
	}

	/**
	 * @param string $uid
	 */
	public function findByUID(string $uid): ?IUser {
		$user = $this->userManager->get($uid);
		return $user;
	}

	/**
	 * @param string $pattern
	 */
	public function findUser(string $pattern): ?IUser {
		$REGEX_FULL_MAIL = '/^[a-zA-Z0-9_.+-].+@[a-zA-Z0-9_.+-]/';

		if (preg_match($REGEX_FULL_MAIL, $pattern) === 1) {
			$user = $this->findByEmail($pattern);
		} else {
			$user = $this->findByUID($pattern);
		}

		return $user;
	}
}
