<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2022 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Service\Group;

use OCA\Workspace\Service\Slugger;
use OCP\IGroup;

class GroupFormatter {
	/**
	 * @param IGroup[] $groups
	 * @return array [
	 *  'gid' => string,
	 *  'displayName' => string,
	 *  'types' => string[],
	 *  'is_ldap' => boolean
	 * ]
	 */
	public static function formatGroups(array $groups): array {
		$groupsFormat = [];

		foreach ($groups as $group) {

			$backendnames = $group->getBackendNames();
			$backendnames = array_map(
				fn ($backendname) => strtoupper($backendname),
				$backendnames
			);

			$groupsFormat[$group->getGID()] = [
				'gid' => $group->getGID(),
				'displayName' => $group->getDisplayName(),
				'types' => $group->getBackendNames(),
				'usersCount' => $group->count(),
				'slug' => Slugger::slugger($group->getGID())
			];
		}

		return $groupsFormat;
	}
}
