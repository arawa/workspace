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

use OCP\IUser;

/**
 * This class represents a Workspace Manager (GE-) group.
 */
class AdminGroup {
	public const GID_PREFIX = 'SPACE-GE-';

	public function __construct(private AdminUserGroup $adminUserGroup,
		private AdminGroupManager $adminGroupManager) {
	}

	public function addUser(IUser $user, string $gid): bool {
		$group = $this->adminGroupManager->get($gid);
		$group->addUser($user);
		$this->adminUserGroup->addUser($user);

		return true;
	}
}
