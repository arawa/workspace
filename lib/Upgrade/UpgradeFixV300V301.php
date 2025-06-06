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

namespace OCA\Workspace\Upgrade;

use OCA\Workspace\Db\GroupFoldersGroupsMapper;
use OCA\Workspace\Db\SpaceMapper;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;

class UpgradeFixV300V301 extends UpgradeV300 {
	public function __construct(
		private GroupFoldersGroupsMapper $groupfoldersGroupsMapper,
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
		private SpaceMapper $spaceMapper,
	) {
		parent::__construct(
			$groupfoldersGroupsMapper,
			$appConfig,
			$groupManager,
			$spaceMapper
		);
	}

	public function upgrade(): void {
		parent::changePrefixForWorkspaceManagerGroups();
		parent::changePrefixForWorkspaceUserGroups();
		$this->changeConventionForSubgroups();
		$this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '1');
	}

	protected function changeConventionForSubgroups(): void {
		$subgroups = $this->groupfoldersGroupsMapper->getSpacenamesGroupIds();
		$subgroups = array_filter($subgroups, function ($subgroup) {
			return !str_starts_with($subgroup['group_id'], 'SPACE-G');
		});
		foreach ($subgroups as $subgroup) {
			$group = $this->groupManager->get($subgroup['group_id']);
			if (is_null($group)) {
				throw new \Exception('Group not found for the migration of workspace to version 3.0.0');
			}
			$oldSubgroup = $subgroup['group_id'];
			$subgroupExploded = explode('-', $oldSubgroup);
			$subgroupSliced = array_slice($subgroupExploded, 0, -1);
			$groupname = implode('-', $subgroupSliced);
			$groupname = 'G-' . $groupname . '-' . $subgroup['space_name'];
			$group->setDisplayName($groupname);
		}
	}
}
