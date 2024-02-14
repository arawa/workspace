<?php

namespace OCA\Workspace\Users;

use OCP\IL10N;
use OCP\IUserManager;
use OCA\Workspace\Exceptions\Notifications\EmailDoesntUniqueException;

class UsersExistCheck {
	public function __construct(private IUserManager $userManager, private IL10N $translate) {
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

    public function checkUserExistByEmail(string $email): bool
    {
        $userEmail = $this->userManager->getByEmail($email);
            
        if (count($userEmail) > 1) {
            $message = $this->translate->t(
                'The %s email address is duplicated in your instance.'
                . ' Impossible to know which users choice or maybe is'
                . ' an error.',
                $email
            );
            throw new EmailDoesntUniqueException(
                'Email address doesn\'t unique',
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
                    'The %s email address is duplicated in your instance.'
                    . ' Impossible to know which users choice or maybe is'
                    . ' an error.',
                    $email
                );
                throw new EmailDoesntUniqueException(
                    'Email address doesn\'t unique',
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
