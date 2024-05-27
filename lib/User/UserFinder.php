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

class UserFinder {
	public function __construct(private IUserManager $userManager) {
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
