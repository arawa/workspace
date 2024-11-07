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

namespace OCA\Workspace\Group\Admin;

use OCP\IGroupManager;
use OCP\IUser;
use Psr\Log\LoggerInterface;

/**
 * This class gathers all users from the
 * OCA\Workspace\Group\Admin\AdminGroup class
 * in the "WorkspacesManagers" group.
 */
class AdminUserGroup {
	public const GID = 'WorkspacesManagers';

	public function __construct(
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
	) {
	}

	public function addUser(IUser $user): bool {
		$group = $this->groupManager->get(self::GID);

		if (is_null($group)) {
			throw new \Exception(sprintf("Impossible to to find the %s group.", self::GID));
		}

		$group->addUser($user);

		return true;
	}

	public function removeUser(IUser $user): void {
		$this->logger->debug('The ' . $user->getUID() . 'User is not manager of any other workspace, removing it from the ' . self::GID . ' group.');
		$workspaceUserGroup = $this->groupManager->get(self::GID);
		$workspaceUserGroup->removeUser($user);
	}
}
