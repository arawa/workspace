<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\User;

use OCP\IUser;
use OCP\IUserManager;

class UserSearcher {
	public function __construct(
		private IUserManager $userManager
	) {
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
