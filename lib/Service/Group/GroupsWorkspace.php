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

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\GroupException;
use OCP\IGroupManager;
use OCP\IUser;

class GroupsWorkspace
{
	private IGroupManager $groupManager;

	public function __construct(
		IGroupManager $groupManager
	)
	{
		$this->groupManager = $groupManager;
	}

	/**
	 * @throws GroupException
	 */
	public function getGroups(string $spaceId): array
	{
		$groupUser = $this->groupManager->get(
			Application::GID_SPACE . Application::ESPACE_USERS_01 . $spaceId
		);

		$groupSpaceManager = $this->groupManager->get(
			Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId
		);

		if (is_null($groupSpaceManager) || is_null($groupUser))
		{
			throw new GroupException('Error to get groups relative to workspace.');
		}

		return [
			$groupUser,
			$groupSpaceManager
		];
	}

	/**
	 * @return String[]
	 */
	public function getGroupsFromUser(IUser $user, array $groupfolder)
	{
		$groups = [];
		foreach($this->groupManager->getUserGroups($user) as $group) {
			if (in_array($group->getGID(), array_keys($groupfolder['groups']))) {
				array_push($groups, $group->getGID());
			}
		}

		return $groups;
	}

	/**
	 * @param IUser[] $users
	 */
	public function transfertUsersToUserGroup($users, string $spaceId): void
	{
		$groupUser = $this->groupManager->get(
			Application::GID_SPACE . Application::ESPACE_USERS_01 . $spaceId
		);

		if (is_null($groupUser))
		{
			throw new GroupException('Error to get user group relative to workspace.');
		}

		foreach($users as $user)
		{
			$groupUser->addUser($user);
		}
	}
}
