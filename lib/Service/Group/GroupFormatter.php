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

/**
 * @psalm-type FormattedGroupSummary = array{
 *     gid: string,
 *     displayName: string,
 *     types: list<string>,
 *     slug: string,
 * }
 * @psalm-type FormattedGroup = array{
 *     gid: string,
 *     displayName: string,
 *     types: list<string>,
 *     usersCount: int,
 *     slug: string,
 * }
 */
class GroupFormatter {
	/**
	 * Loads every member of non-workspace groups to count them: prefer
	 * formatGroupsWithoutUsersCount() when the count is not displayed.
	 *
	 * @param IGroup[] $groups
	 * @return array<string, FormattedGroup> keyed by gid
	 */
	public static function formatGroups(array $groups): array {
		$groupsFormat = [];

		foreach ($groups as $group) {
			$groupsFormat[$group->getGID()] = self::formatGroupWithUsersCount($group, self::countUsers($group));
		}

		return $groupsFormat;
	}

	/**
	 * A group just created has no member yet, so there is nothing to count.
	 *
	 * @return FormattedGroup
	 */
	public static function formatNewGroup(IGroup $group): array {
		return self::formatGroupWithUsersCount($group, 0);
	}

	/**
	 * @param IGroup[] $groups
	 * @return array<string, FormattedGroupSummary> keyed by gid
	 */
	public static function formatGroupsWithoutUsersCount(array $groups): array {
		$groupsFormat = [];

		foreach ($groups as $group) {
			$groupsFormat[$group->getGID()] = self::formatGroup($group);
		}

		return $groupsFormat;
	}

	/**
	 * @return FormattedGroupSummary
	 */
	private static function formatGroup(IGroup $group): array {
		return [
			'gid' => $group->getGID(),
			'displayName' => $group->getDisplayName(),
			'types' => $group->getBackendNames(),
			'slug' => Slugger::slugger($group->getGID()),
		];
	}

	/**
	 * @return FormattedGroup
	 */
	private static function formatGroupWithUsersCount(IGroup $group, int $usersCount): array {
		return [
			...self::formatGroup($group),
			'usersCount' => $usersCount,
		];
	}

	private static function countUsers(IGroup $group): int {
		if (UserGroup::isWorkspaceGroup($group)) {
			// IGroup::count() may return false when the backend cannot count
			return (int)$group->count();
		}

		$users = array_filter($group->getUsers(), fn ($user) => $user->isEnabled());

		return count($users);
	}
}
