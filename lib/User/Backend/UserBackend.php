<?php

namespace OCA\Workspace\User\Backend;

use OC\User\Backend;
use OC\User\NoUserException;
use OCA\Workspace\Db\SpaceMapper;
use OCP\IUserBackend;
use OCP\Notification\IManager as INotificationManager;
use OCP\User\Backend\ILimitAwareCountUsersBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class UserBackend implements IUserBackend, UserInterface, ILimitAwareCountUsersBackend, IProvideEnabledStateBackend {

	/** @var array<string,string> */
	private $_users = null;

	public function __construct(
		protected INotificationManager $notificationManager,
		protected LoggerInterface $logger,
		private SpaceMapper $spaceMapper,
	) {
	}

	private function initUsers(): void {
		if ($this->_users !== null) {
			return;
		}

		$users = [];
		$spaces = $this->spaceMapper->findAll();
		/** @var Space $space */
		foreach ($spaces as $space) {
			$users['SPACE-UWS-' . $space->getSpaceId()] = [
				'displayName' => $space->getSpaceName(),
			];
		}
		$this->_users = $users;
	}

	/**
	 * checks whether the user is allowed to change their avatar in Nextcloud
	 *
	 * @param string $uid the Nextcloud user name
	 * @return boolean either the user can or cannot
	 * @throws \Exception
	 */
	public function canChangeAvatar($uid) {
		return false;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param integer $limit
	 * @param integer $offset
	 * @return string[] an array of all uids
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$this->initUsers();
		$limit = (is_int($limit) && $limit >= 0) ? $limit : null;
		if ($limit === null && $offset === 0 && $search === '') {
			return array_keys($this->_users);
		}
		if ($search === '') {
			return array_slice(array_keys($this->_users), $offset, $limit);
		}
		$search = strtolower($search);
		$users = [];
		$count = 0;
		foreach ($this->_users as $uid => $user) {
			if (str_contains(strtolower($user['displayName']), $search)) {
				if ($count >= $offset) {
					$users[] = $uid;
				}
				$count++;
				if ($limit !== null && $count >= $offset + $limit) {
					break;
				}
			}
		}
		return $users;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 * @throws \Exception when connection could not be established
	 */
	public function userExists($uid) {
		$this->initUsers();
		return array_key_exists($uid, $this->_users);
	}

	/**
	 * returns whether a user was deleted in LDAP
	 *
	 * @param string $uid The username of the user to delete
	 * @return bool
	 */
	public function deleteUser($uid) {
		return false;
	}

	/**
	 * get the user's home directory
	 *
	 * @param string $uid the username
	 * @return bool|string
	 * @throws NoUserException
	 * @throws \Exception
	 */
	public function getHome($uid) {
		return false;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string|false display name
	 */
	public function getDisplayName($uid) {
		$this->initUsers();
		if (isset($this->_users[$uid]) && isset($this->_users[$uid]['displayName'])) {
			return $this->_users[$uid]['displayName'];
		}
		return false;
	}

	/**
	 * set display name of the user
	 * @param string $uid user ID of the user
	 * @param string $displayName new display name of the user
	 * @return string|false display name
	 */
	public function setDisplayName($uid, $displayName) {
		return false;
	}

	/**
	 * Get a list of all display names
	 *
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$this->initUsers();
		return array_combine(
			array_keys($this->_users),
			array_column($this->_users, 'displayName')
		);
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\User\Backend::CREATE_USER etc.
	 */
	public function implementsActions($actions) {
		return (bool)(
			// Backend::CHECK_PASSWORD
			// | Backend::GET_HOME
			(Backend::GET_DISPLAYNAME
			// | (($this->access->connection->ldapUserAvatarRule !== 'none') ? Backend::PROVIDE_AVATAR : 0)
			| Backend::COUNT_USERS)
			// | (((int)$this->access->connection->turnOnPasswordChange === 1)? Backend::SET_PASSWORD :0)
			// | $this->userPluginManager->getImplementedActions())
			& $actions);
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}

	/**
	 * counts the users in LDAP
	 */
	public function countUsers(int $limit = 0): int|false {
		$this->initUsers();
		return count($this->_users);
	}


	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return 'WorkSpace';
	}

	/**
	 * @since 28.0.0
	 *
	 * @param callable():bool $queryDatabaseValue A callable to query the enabled state from database
	 */
	public function isUserEnabled(string $uid, callable $queryDatabaseValue): bool {
		if (str_starts_with($uid, 'SPACE-UWS-')) {
			$spaceId = (int)substr($uid, 10);
			if ($spaceId !== 0) {
				$space = $this->spaceMapper->find($spaceId);
				return $space !== null;
			}
			return false;
		}
		return true;
	}

	/**
	 * @since 28.0.0
	 *
	 * @param callable():bool $queryDatabaseValue A callable to query the enabled state from database
	 * @param callable(bool):void $setDatabaseValue A callable to set the enabled state in the database.
	 */
	public function setUserEnabled(string $uid, bool $enabled, callable $queryDatabaseValue, callable $setDatabaseValue): bool {
		if (str_starts_with($uid, 'SPACE-UWS-')) {
			return !$enabled; // refuse change
		}
		return true;
	}

	/**
	 * Get the list of disabled users, to merge with the ones disabled in database
	 *
	 * @since 28.0.0
	 * @since 30.0.0 $search parameter added
	 *
	 * @return string[]
	 */
	public function getDisabledUserList(?int $limit = null, int $offset = 0, string $search = ''): array {
		return [];
	}
}
