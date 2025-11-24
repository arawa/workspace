<?php

namespace OCA\Workspace\Users;

use OCA\Workspace\Exceptions\Notifications\EmailDoesntUniqueException;
use OCP\IL10N;
use OCP\IUserManager;

class UsersExistCheck {
	public function __construct(
		private IUserManager $userManager,
		private IL10N $translate,
	) {
	}

	/**
	 * Check if all users exists in a list.
	 *
	 * @param String[] $users - example [ 'users1', 'users2', 'users3' ]
	 */
	public function checkUsersExist(array $users): bool {
		foreach ($users as $user) {
			$userNotExist = $this->userManager->userExists($user);
			if (!$userNotExist) {
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

	public function checkUserExistByEmail(string $email): bool {
		$userEmail = $this->userManager->getByEmail($email);

		if (count($userEmail) > 1) {
			$message = $this->translate->t(
				'Email address %s is linked to multiple users.'
				. ' Impossible to know which user to choose.',
				$email
			);
			throw new EmailDoesntUniqueException(
				'Email address is not unique',
				$message
			);
		}

		if (!$userEmail) {
			return false;
		}

		return true;
	}

	public function checkUsersExistByEmail(array $emails): bool {

		foreach ($emails as $email) {
			$userEmail = $this->userManager->getByEmail($email);

			if (count($userEmail) > 1) {
				$message = $this->translate->t(
					'Email address %s is linked to multiple users.'
					. ' Impossible to know which user to choose.',
					$email
				);
				throw new EmailDoesntUniqueException(
					'Email address is not unique',
					$message
				);
			}

			if (!$userEmail) {
				return false;
			}
		}

		return true;
	}
}
