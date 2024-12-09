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
use OCA\Workspace\Db\GroupFoldersGroupsMapper;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCP\IGroup;
use OCP\IGroupManager;

class ConnectedGroupsService {


	private ?array $linkedSpaceGroups = null;
	private array $linkedGroupsWSGroups = [];

	public function __construct(
		private IGroupManager $groupManager,
		private GroupFoldersGroupsMapper $mapper,
		private SpaceMapper $spaceMapper
	) {
	}

	private function getLinkedSpaceGroups(): array {
		if ($this->linkedSpaceGroups === null) {
			$this->initLinkedSpaceGroups();
		}
		return $this->linkedSpaceGroups;
	}

	private function initLinkedSpaceGroups(): void {
		$connectedGroups = $this->mapper->findAllAddedGroups();

		if (empty($connectedGroups)) {
			$this->linkedSpaceGroups = [];
			return;
		}

		$data = [];
		foreach($connectedGroups as $connectedGroup) {
			$gid = 'SPACE-U-' . $connectedGroup->getSpaceId();
			$linked_gid = $connectedGroup->getGid();
			$data[$gid][] = $linked_gid;
			$this->linkedGroupsWSGroups[$linked_gid][] = $gid;
		}
		
		$this->linkedSpaceGroups = $data;
	}

	/**
	 * @param string $gid
	 * @param array $spaceGids
	 * @return bool
	 */
	public function isConnectedToWorkspace(string $gid, array $spaceGids) : bool {
		$linkedSpaceGroups = $this->getLinkedSpaceGroups();

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
		if ($this->linkedSpaceGroups === null) {
			$this->initLinkedSpaceGroups();
		}
		if (isset($this->linkedGroupsWSGroups[$gid])) {
			return $this->linkedGroupsWSGroups[$gid];
		}
		return null;
	}

	/**
	 * @param string $spaceGid
	 * @return array|null
	 */
	public function getConnectedGroupsToSpaceGroup(string $spaceGid): ?array {
		$linkedSpaceGroups = $this->getLinkedSpaceGroups();

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
	public function hasConnectedGroups(string $gid, ?string $gidUserGroup = null) : bool {
		
		$linkedSpaceGroups = $this->getLinkedSpaceGroups();

		if (empty($linkedSpaceGroups)) {
			return false;
		}

		if (!is_null($gidUserGroup)) {
			$values = $linkedSpaceGroups[$gidUserGroup];
			if (!is_null($values)) {
				return in_array($gid, $values);
			}
			return false;
		}

		return isset($linkedSpaceGroups[$gid]);
	}

	/**
	 * @deprecated don't use this function
	 * @todo delete this function
	 */
	public function add(IGroup $group, Space $space): bool {
		/*
				$connectedGroup = new ConnectedGroup();
				$connectedGroup->setSpaceId($space->getSpaceId());
				$connectedGroup->setGid($group->getGid());

				$this->mapper->insert($connectedGroup);
		*/
		return true;
	}

    public function isUserConnectedGroup(string $uid): bool
    {
        $res = $this->mapper->isUserConnectedGroup($uid);
        return empty($res);
    }
}
