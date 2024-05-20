<?php

/**
 * @copyright Copyright (c) 2024 Arawa
 *
 * @author 2024 Sébastien Marinier <seb@smarinier.net>
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

use OCP\IGroupManager;

class ConnectedGroupsService {

	private static $LINKED_SPACE_GROUPS = [
		'SPACE-U-2' => ['Scientists', 'Chemists']
	];

	private static $LINKED_GROUPS_IN = [

	];

	public function __construct(private IGroupManager $groupManager) {
		$this->init();
	}

	private function init(): void {
		foreach (self::$LINKED_SPACE_GROUPS as $gid => $linked_gids) {
			foreach($linked_gids as $gidIn) {
				if (!isset(self::$LINKED_GROUPS_IN[$gidIn])) {
					self::$LINKED_GROUPS_IN[$gidIn] = [ $gid];
					continue;
				}
				self::$LINKED_GROUPS_IN[$gidIn][] = [ $gid];
			}
		}
	}

	/**
	 * @param string $gid
	 * @param array $spaceGids
	 * @return bool
	 */
	public static function isConnectedToWorkspace(string $gid, array $spaceGids) : bool {
		foreach ($spaceGids as $spaceGid) {
			if (isset(self::$LINKED_SPACE_GROUPS[$spaceGid])) {
				return in_array($gid, self::$LINKED_SPACE_GROUPS[$spaceGid]);
			}
		}
		return false;
	}

	/**
	 * @param string $gid
	 * @return array|null
	 */
	public function getConnectedSpaceToGroupIds(string $gid): ?array {
		if (isset(self::$LINKED_GROUPS_IN[$gid])) {
			return self::$LINKED_GROUPS_IN[$gid];
		}
		return null;
	}

	/**
	 * @param string $spaceGid
	 * @return array|null
	 */
	public function getConnectedGroupsToSpace(string $spaceGid): ?array {
		if (!isset(self::$LINKED_SPACE_GROUPS[$spaceGid])) {
			return null;
		}
		$groups = [];
		foreach (self::$LINKED_SPACE_GROUPS[$spaceGid] as $gid) {
			$groups[] = $this->groupManager->get($gid);
		}
		return $groups;
	}

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function hasConnectedgroups(string $gid) : bool {
		return isset(self::$LINKED_SPACE_GROUPS[$gid]);
	}
}
