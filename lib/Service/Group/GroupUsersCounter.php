<?php

/**
 * @copyright Copyright (c) 2026 Arawa
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
use OCP\IUserManager;

/**
 * Counts group members without verifying each of them.
 *
 * IGroup::getUsers() instantiates and verifies every member through
 * IUserManager::get(), which costs one existence check per user on the
 * user backend (an LDAP round trip for LDAP users) plus several database
 * queries. IGroup::searchUsers() returns lazy user objects instead, and
 * counting only needs their uids.
 *
 * IGroup::count() - IGroup::countDisabled() is not an option: user_ldap does
 * not implement ICountDisabledInGroup, so disabled LDAP users would be counted.
 */
class GroupUsersCounter {

	/** @var array<string, true>|null */
	private ?array $disabledUids = null;

	/**
	 * A group is often both added to a workspace and connected to its user
	 * group, so its members would otherwise be listed twice per request.
	 *
	 * @var array<string, array<string, true>>
	 */
	private array $enabledUidsByGid = [];

	public function __construct(
		private IUserManager $userManager,
	) {
	}

	/**
	 * @return array<string, true> uids of the group, as keys
	 */
	public function getUids(IGroup $group): array {
		$uids = [];
		foreach ($group->searchUsers('') as $user) {
			$uids[$user->getUID()] = true;
		}

		return $uids;
	}

	/**
	 * @return array<string, true> uids of the enabled members of the group, as keys
	 */
	public function getEnabledUids(IGroup $group): array {
		return $this->enabledUidsByGid[$group->getGID()]
			??= array_diff_key($this->getUids($group), $this->getDisabledUids());
	}

	public function countEnabledUsers(IGroup $group): int {
		return count($this->getEnabledUids($group));
	}

	/**
	 * Same source as IUser::isEnabled(): the core "enabled" preference, plus
	 * the users a backend reports as disabled (e.g. LDAP remnants).
	 *
	 * @return array<string, true>
	 */
	private function getDisabledUids(): array {
		if ($this->disabledUids === null) {
			$this->disabledUids = [];
			foreach ($this->userManager->getDisabledUsers() as $user) {
				$this->disabledUids[$user->getUID()] = true;
			}
		}

		return $this->disabledUids;
	}
}
