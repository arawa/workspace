<?php
namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCP\IGroupManager;
use OCP\IUserSession;

Class UserService {

	/** @var $groupManager */
	private $groupManager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IGroupManager $group,
		IUserSession $userSession) {

		$this->groupManager = $group;
		$this->userSession = $userSession;

	}

	/**
	 *
	 * Given a IUser, returns an array containing all the user information
	 * needed for the frontend
	 *
	 * @param IUser $user
	 * @param array $space
	 *
	 * @return array
	 *
	 */
	public function formatUser($user, $space) {
	
		if (is_null($user)) {
			return;
		}

		// Gets the workspace subgroups the user is member of
		$groups = [];
		foreach($this->groupManager->getUserGroups($user) as $group) {
			if (substr_compare($group->getGID(), $space['id'], -strlen($spacei['id'])) === 0
				|| $group->getGID() === Application::ESPACE_MANAGER_01 . $space['mount_point']
				|| $group->getGID() === Application::ESPACE_USERS_01 . $space['mount_point']
			) {
				array_push($groups, $group->getGID());
			}
		};

		// Returns a user that is valid for the frontend
		return array(
			'name' => $user->getDisplayName(),
			'email' => $user->getEmailAddress(),
			'groups' => $groups
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
		// TODO This must use the application constants
		$workspaceAdminGroups = $this->groupManager->search('GE-');
		foreach($workspaceAdminGroups as $group) {
			if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $group->getGID())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $name The workspace name
	 * @return boolean true if user is space manager of the specified workspace, false otherwise
	*/
	public function isSpaceManagerOfSpace($name) {
		// TODO This must use the application constants
		$workspaceAdminGroup = $this->groupManager->search('GE-' . $name);

		if (count($workspaceAdminGroup) == 0) {
			// TODO Log error
			return false;
		}

		if (count($workspaceAdminGroup) > 1) {
			// TODO Several group match the space name, we need to find the good one
		}

		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $workspaceAdminGroup[0]->getGID())) {
			return true;
		}
		return false;
	}

}
