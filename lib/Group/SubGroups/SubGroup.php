<?php

/**
 * @copyright Copyright (c) 2024 Arawa
 *
 * @author 2024 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Group\SubGroups;

use OCA\Workspace\Exceptions\GroupException;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCP\AppFramework\Http;
use OCP\IGroup;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

class SubGroup {

	public const PREFIX_GID = 'SPACE-G-';
	public const PREFIX_DISPLAY_NAME = 'G-';

	public function __construct(
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
	) {
	}

	public function get(array $gids): array {
		$groups = [];
		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);
			if (is_null($group)) {
				$this->logger->warning(
					"Be careful, this $gid group does not exists in the oc_groups table."
					. ' The group is still present in the oc_group_folders_groups table.'
					. ' To fix this inconsistency, recreate the group using occ commands.'
				);
				continue;
			}
			$groups[] = $group;
		}
		return $groups;
	}

	public function getGroupsFormatted(array $gids): array {
		return GroupFormatter::formatGroups($this->get($gids));
	}

	public function create(string $groupname, int $id, string $spacename): IGroup {
		$gid = sprintf('%s%s-%s', self::PREFIX_GID, $groupname, $id);
		$displayName = sprintf('%s%s-%s', self::PREFIX_DISPLAY_NAME, $groupname, $spacename);

		$group = $this->groupManager->get($gid);

		$groupsSearched = $this->groupManager->search($displayName);
		$groupnames = array_map(fn ($group) => $group->getDisplayName(), $groupsSearched);

		if (!is_null($group)) {
			if (in_array($displayName, $groupnames)) {
				throw new GroupException("Group with display name $displayName already exists.", Http::STATUS_CONFLICT);
			}
		}

		$count = 1;
		while (!is_null($group)) {
			$gid = sprintf('%s%s-%s', self::PREFIX_GID, "$groupname$count", $id);
			$group = $this->groupManager->get($gid);
			if (is_null($group)) {
				break;
			}
			$count++;
		}

		$group = $this->groupManager->createGroup($gid);
		$group->setDisplayName($displayName);

		return $group;
	}
}
