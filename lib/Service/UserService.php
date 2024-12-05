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

use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

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
	 * @deprecated 3.1.0|4.0.0
	 * @uses OCA\Workspace\Users\UserFormatter
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

		if (!isset($space['groups'])) {
			throw new \Exception('The "groups" key is not presetn');
		}

		if (!is_array($space['groups'])) {
			throw new \Exception('The "groups" key is not an array');
		}

		foreach ($this->groupManager->getUserGroups($user) as $group) {
			if (in_array($group->getGID(), array_keys($space['groups']))) {
				array_push($groups, $group->getGID());
			}
		}

		// Returns a user that is valid for the frontend
		return [
			'uid' => $user->getUID(),
			'name' => $user->getDisplayName(),
			'email' => $user->getEmailAddress(),
			'subtitle' => $user->getEmailAddress(),
			'groups' => $groups,
			'role' => $role
		];
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
		foreach ($workspaceAdminGroups as $group) {
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
	 * Return `true` if the user can be removed from workspace manager group (SPACE-GE), Otherwise, `false`.
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function canRemoveWorkspaceManagers(IUser $user): bool {
		$canRemove = false;
		$groups = $this->groupManager->getUserGroups($user);
		$allManagersGroups = array_filter(
			$groups,
			fn ($group) => str_starts_with($group->getGID(), 'SPACE-GE')
		);

		$canRemove = count($allManagersGroups) > 0 && count($allManagersGroups) <= 1 ? true : false;

		if (!$canRemove) {
			$this->logger->debug('User is still manager of other workspaces, will not remove it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group.');
		}

		return $canRemove;
	}
	
	/**
	 * This function removes a GE from the WorkspaceManagers group when necessary
	 *
	 * @param IUser $user
	 * @return void
	 *
	 * @deprecated
	 *
	 * @uses OCA\Workspace\Group\Admin\AdminUserGroup::removeUser
	 */
	public function removeGEFromWM(IUser $user): void {
		$this->logger->debug('User is not manager of any other workspace, removing it from the ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' group.');
		$workspaceUserGroup = $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS);
		$workspaceUserGroup->removeUser($user);

		return;
	}
}
