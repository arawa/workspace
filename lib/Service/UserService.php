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
	* @return boolean true if user is general admin, false otherwise
	*/
	public function isUserGeneralAdmin() {
		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GENERAL_MANAGER)) {
			return true;
		} else {
			return false;
		}
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
	 *
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
