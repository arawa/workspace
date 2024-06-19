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

use OCA\Workspace\Db\ConnectedGroup;
use OCA\Workspace\Db\ConnectedGroupMapper;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCP\IGroup;
use OCP\IGroupManager;

class ConnectedGroupsService {

	private static $LINKED_GROUPS_IN = [

	];

	public function __construct(
		private IGroupManager $groupManager,
		private ConnectedGroupMapper $mapper,
		private SpaceMapper $spaceMapper
	) {
		$this->init();
	}

	private function init(): void {
		$linkedSpaceGroups = $this->initLinkedSpaceGroups();

		if (is_null($linkedSpaceGroups))
			return;

		foreach ($linkedSpaceGroups as $gid => $linked_gids) {
			foreach($linked_gids as $gidIn) {
				if (!isset(self::$LINKED_GROUPS_IN[$gidIn])) {
					self::$LINKED_GROUPS_IN[$gidIn] = [ $gid ];
					continue;
				}
				self::$LINKED_GROUPS_IN[$gidIn][] = [ $gid ];
			}
		}
	}

	private function initLinkedSpaceGroups(): ?array {
		$connectedGroups = $this->mapper->findAll();

		if (empty($connectedGroups))
			return null;

		$data = [];
		foreach($connectedGroups as $connectedGroup) {
			$data['SPACE-U-' . $connectedGroup->getSpaceId()][] = $connectedGroup->getGid();
		}
		
		return $data;
	}


	/**
	 * @param string $gid
	 * @param array $spaceGids
	 * @return bool
	 */
	public static function isConnectedToWorkspace(string $gid, array $spaceGids) : bool {
		$linkedSpaceGroups = self::initLinkedSpaceGroups();

		foreach ($spaceGids as $spaceGid) {
			if (isset($linkedSpaceGroups[$spaceGid])) {
				return in_array($gid, $linkedSpaceGroups[$spaceGid]);
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
	public function getConnectedGroupsToSpaceGroup(string $spaceGid): ?array {
		$linkedSpaceGroups = $this->initLinkedSpaceGroups();

		if (!isset($linkedSpaceGroups[$spaceGid])) {
			return null;
		}
		$groups = [];
		foreach ($linkedSpaceGroups[$spaceGid] as $gid) {
			$groups[] = $this->groupManager->get($gid);
		}
		return $groups;
	}

	/**
	 * @param string $gid
	 * @param ?string $gidUserGroup - Specify the gid user group
	 * @return bool
	 */
	public function hasConnectedgroups(string $gid, ?string $gidUserGroup = null) : bool {
		
		$linkedSpaceGroups = $this->initLinkedSpaceGroups();

		if (is_null($linkedSpaceGroups))
			return false;

		if (!is_null($gidUserGroup)) {
			$values = $linkedSpaceGroups[$gidUserGroup];
			if (!is_null($values))
				return in_array($gid, $values);	
			return false;
		}
		
		$i = array_keys($linkedSpaceGroups)[0];
		$values = array_values($linkedSpaceGroups[$i]);
		return in_array($gid, $values);
	}

	public function add(IGroup $group, Space $space): bool {

		$connectedGroup = new ConnectedGroup();
		$connectedGroup->setSpaceId($space->getSpaceId());
		$connectedGroup->setGid($group->getGid());

		$this->mapper->insert($connectedGroup);

		return true;
	}
}
