<?php

namespace OCA\Workspace\User\Backend;

use OC\User\Backend;
use OC\User\NoUserException;
use OCA\Workspace\Db\SpaceMapper;
use OCP\IUserBackend;
use OCP\Notification\IManager as INotificationManager;
use OCP\User\Backend\ILimitAwareCountUsersBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class UserBackend implements IUserBackend, UserInterface, ILimitAwareCountUsersBackend {

	// static private $_users = [
	// 	'SPACE-UWS-2' => [
	// 		'displayName' => 'Workspace2',
	// 	],
	// ];

	private array $_users = [];

	public function __construct(
		protected INotificationManager $notificationManager,
		protected LoggerInterface $logger,
		private SpaceMapper $spaceMapper,
	) {
		$this->initUsers();
	}

	private function initUsers(): void {
		if ($this->_users !== []) {
			return;
		}

		$spaces = $this->spaceMapper->findAll();
		foreach ($spaces as $space) {
			$this->_users['SPACE-UWS-' . $space->getSpaceId()] = [
				'displayName' => 'Workspace' . $space->getSpaceId(),
			];
		}
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
		return array_keys($this->_users);
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 * @throws \Exception when connection could not be established
	 */
	public function userExists($uid) {
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
		return count($this->_users);
	}


	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return 'WorkSpace';
	}

}
