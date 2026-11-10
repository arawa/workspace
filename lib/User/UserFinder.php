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

use OCA\Workspace\Exceptions\Notifications\EmailDoesntUniqueException;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UserFinder {
	public function __construct(
		private IUserManager $userManager,
		private IL10N $translate,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param string $email
	 * @throws EmailDoesntUniqueException when several users share this email
	 */
	public function findByEmail(string $email): ?IUser {
		$users = $this->userManager->getByEmail($email);

		if (count($users) > 1) {
			throw new EmailDoesntUniqueException(
				'Email address is not unique',
				$this->translate->t(
					'Email address %s is linked to multiple users.'
					. ' Impossible to know which user to choose.',
					$email
				)
			);
		}

		return $users[0] ?? null;
	}

	/**
	 * @param string $uid
	 */
	public function findByUID(string $uid): ?IUser {
		$user = $this->userManager->get($uid);
		return $user;
	}

	/**
	 * LDAP users stay unknown to Nextcloud until a search maps them (user_ldap),
	 * so a miss triggers one search before giving up.
	 *
	 * @param string $pattern username or email
	 */
	public function findUser(string $pattern): ?IUser {
		$user = $this->findKnownUser($pattern);
		if ($user !== null || $pattern === '') {
			return $user;
		}

		try {
			$this->userManager->searchDisplayName($pattern, 10);
		} catch (\Exception $e) {
			// Best effort: the user is reported as unknown.
			$this->logger->warning('Could not look up user ' . $pattern, ['exception' => $e]);
		}

		return $this->findKnownUser($pattern);
	}

	/**
	 * Email first: since Nextcloud 31 the local backend's get() also accepts an
	 * email and silently returns the first matching user, which would skip the
	 * uniqueness check. Some UIDs contain an "@" (SAML, OIDC), so the shape of
	 * the pattern cannot tell a UID from an email.
	 */
	private function findKnownUser(string $pattern): ?IUser {
		return $this->findByEmail($pattern) ?? $this->findByUID($pattern);
	}
}
