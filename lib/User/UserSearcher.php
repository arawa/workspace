<?php

namespace OCA\Workspace\User;

use OCP\IUser;
use OCP\IUserManager;

class UserSearcher {
	public function __construct(private IUserManager $userManager) {
	}

	/**
	 * @param string $email
	 * @return IUser[]
	 */
	public function searchUsersByMailing(string $email): array {
		return $this->userManager->getByEmail($email);
	}

	/**
	 * @param string $displayname
	 * @return IUser[]
	 */
	public function searchUsersByDisplayName(string $displayname): array {
		$users = [];

		$displayname = $displayname === '*' ? '' : $displayname;
		$users = $this->userManager->searchDisplayName($displayname, 50);

		$users = array_unique($users, SORT_REGULAR);

		return $users;
	}

	/**
	 * @param string $pattern
	 * @return IUser[]
	 */
	public function searchUsers(string $pattern): array {
		$users = [];
		$REGEX_FULL_MAIL = '/^[a-zA-Z0-9_.+-].+@[a-zA-Z0-9_.+-]/';

		if (preg_match($REGEX_FULL_MAIL, $pattern) === 1) {
			$users = $this->searchUsersByMailing($pattern);
		} else {
			$users = $this->searchUsersByDisplayName($pattern);
		}

		/**
		 * Change OC\User\LazyUser to OC\User\User.
		 */
		$users = array_map(fn ($user) => $this->userManager->get($user->getUID()), $users);

		return $users;
	}
}
