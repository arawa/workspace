<?php

/**
 * @copyright Copyright (c) 2017 Arawa
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

use OCP\IGroup;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

/**
 * Resolves the groups attached to a workspace's groupfolder
 * and sorts them between workspace groups and added groups.
 */
class WorkspaceGroupsResolver {
	public function __construct(
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param string[] $gids the gids attached to the groupfolder
	 * @return array{
	 *               workspaceGroups: IGroup[],
	 *               addedGroups: IGroup[],
	 *               userGroup: ?IGroup
	 *               }
	 */
	public function resolve(array $gids): array {
		$workspaceGroups = [];
		$addedGroups = [];
		$userGroup = null;

		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);

			if (is_null($group)) {
				$this->logger->warning(
					"Be careful, the $gid group does not exist in the oc_groups table."
					. ' The group is still present in the oc_group_folders_groups table.'
					. ' To fix this inconsistency, recreate the group using occ commands.'
				);
				continue;
			}

			if (UserGroup::isWorkspaceGroup($group)) {
				$workspaceGroups[] = $group;
			} else {
				$addedGroups[] = $group;
			}

			if (UserGroup::isWorkspaceUserGroupId($gid)) {
				$userGroup = $group;
			}
		}

		return [
			'workspaceGroups' => $workspaceGroups,
			'addedGroups' => $addedGroups,
			'userGroup' => $userGroup,
		];
	}
}
