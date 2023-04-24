<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

namespace OCA\Workspace\Service;

use OCP\IUser;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCA\Workspace\Service\Group\UserGroup;
use Psr\Log\LoggerInterface;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;

class UserService {
	public function __construct(
		private IGroupManager $groupManager,
		private IUserSession $userSession,
		private LoggerInterface $logger
	) {
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

	public function formatUser(IUser $user, array $space, string $role): array|null {
		if (is_null($user)) {
			return null;
		}

		// Gets the workspace subgroups the user is member of
		$groups = [];
		foreach ($this->groupManager->getUserGroups($user) as $group) {
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
	public function isUserGeneralAdmin(): bool {
		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), ManagersWorkspace::GENERAL_MANAGER)) {
			return true;
		}
		return false;
	}

	/**
	 * @return boolean true if user is a space manager, false otherwise
	 */
	public function isSpaceManager(): bool {
		$workspaceAdminGroups = $this->groupManager->search(WorkspaceManagerGroup::getPrefix());
		foreach($workspaceAdminGroups as $group) {
			if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $group->getGID())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return boolean true if user is space manager or general manager, false otherwise
	 * @todo Can we move this function in the lib/AppInfo/Application.php ?
	 */
	public function canAccessApp(): bool {
		if ($this->isSpaceManager() || $this->isUserGeneralAdmin()) {
			return true;
		}
		return false;
	}

	/**
	 * @param array $id The space id
	 * @return boolean true if user is space manager of the specified workspace, false otherwise
	*/
	public function isSpaceManagerOfSpace(array $space): bool {

		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), WorkspaceManagerGroup::get($space['id']))) {
			return true;
		}
		return false;
	}

	/**
	 * This function removes a GE from the WorkspaceManagers group when necessary
	 */
	public function removeGEFromWM(IUser $user, array|object $space): void {
		$found = false;
		$groups = $this->groupManager->getUserGroups($user);

		// Checks if the user is member of the GE- group of another workspace
		foreach ($groups as $group) {
			$gid = $group->getGID();
			if (strpos($gid, WorkspaceManagerGroup::get($space['id'])) === 0 &&
				$gid !== UserGroup::get($space['id'])
			) {
				$found = true;
				break;
			}
		}

		// Removing the user from the WorkspacesManagers group if needed
		if (!$found) {
			$this->logger->debug('User is not manager of any other workspace, removing it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group.');
			$workspaceUserGroup = $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS);
			$workspaceUserGroup->removeUser($user);
		} else {
			$this->logger->debug('User is still manager of other workspaces, will not remove it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group.');
		}

		return;
	}
}
