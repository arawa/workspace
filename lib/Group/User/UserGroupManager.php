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

namespace OCA\Workspace\Group\User;

use OCP\IGroup;
use OCP\IGroupManager;

/**
 * This class represents the manager of the
 * OCA\Workspace\Group\User\UserGroup.
 */
class UserGroupManager {
	public function __construct(public IGroupManager $groupManager) {
	}

	public function get(string $gid): IGroup {
		$group = $this->groupManager->get($gid);

		if (is_null($group)) {
			throw new \Exception("The $gid group is not exist.");
		}

		return $group;
	}

	public static function findWorkspaceManager(array $workspace): string {
		if (!array_key_exists('groups', $workspace)) {
			throw new \Exception('The "groups" key is not present in the $workspace variable.');
		}

		$gids = array_keys($workspace['groups']);

		$groupname = array_values(
			array_filter(
				$gids,
				fn ($gid) => str_starts_with($gid, UserGroup::GID_PREFIX)
			)
		)[0];

		return $groupname;
	}
}
