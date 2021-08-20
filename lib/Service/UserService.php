<?php
namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

Class UserService {

	/** @var $IGroupManager */
	private $groupManager;

	/** @var $ILogger */
	private $logger;

	/** @var $IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IGroupManager $group,
		ILogger $logger,
		IUserManager $userManager,
		IUserSession $userSession
	)
	{

		$this->groupManager = $group;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;

	}

	/**
	 *
	 * Given a IUser, returns an array containing all the user information
	 * needed for the frontend
	 *
	 * @param IUser $user
	 * @param array $space
	 * @param string $role
	 *
	 * @return array|null
	 *
	 */

	public function formatUser($user, $space, $role) {
	
		if (is_null($user)) {
			return;
		}

		// Gets the workspace subgroups the user is member of
		$groups = [];
		foreach($this->groupManager->getUserGroups($user) as $group) {
			if (in_array($group->getGID(), array_keys($space['groups']))) {
				array_push($groups, $group->getGID());
			}
		}

		// Returns a user that is valid for the frontend
		return array(
			'uid' => $user->getUID(),
			'name' => $user->getDisplayName(),
			'email' => $user->getEmailAddress(),
			'subtitle' => $user->getEmailAddress(),
			'groups' => $groups,
			'role' => $role
		);

	}

	/**
	 * @return boolean true if user is general admin, false otherwise
	*/
	public function isUserGeneralAdmin() {
		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GENERAL_MANAGER)) {
			return true;
		}
		return false;
	}

	/**
	 * @return boolean true if user is a space manager, false otherwise
	*/
	public function isSpaceManager() {
		$workspaceAdminGroups = $this->groupManager->search(Application::ESPACE_MANAGER_01);
		foreach($workspaceAdminGroups as $group) {
			if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $group->getGID())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $id The space id
	 * @return boolean true if user is space manager of the specified workspace, false otherwise
	*/
	public function isSpaceManagerOfSpace($id) {

		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $id)) {
			return true;
		}
		return false;
	}

	/** 
	 *
	 * This function removes a GE from the WorkspaceManagers group when necessary
	 *
	 */
	public function removeGEFromWM(IUser $user, array $space) {
		$found = false;
		$groups = $this->groupManager->getUserGroups($user);

		foreach($groups as $group) {
			$groupName = $group->getDisplayName();
			if (strpos($groupName, Application::ESPACE_MANAGER_01) === 0 &&
				$groupName !== Application::ESPACE_MANAGER_01 . $space['space_name'] &&
				$groupName !== Application::GROUP_WKSUSER
			) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->logger->debug('User is not manager of any other workspace, removing it from the WorkspacesManagers group.');
			$workspaceUserGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
			$workspaceUserGroup->removeUser($user);
		} else {
			$this->logger->debug('User is still manager of other workspaces, will not remove it from the WorkspacesManagers group.');
		}

		return;
	}

}
