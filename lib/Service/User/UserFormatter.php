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

namespace OCA\Workspace\Service\User;

use OCA\Workspace\Roles;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCP\IURLGenerator;
use OCP\IUser;

class UserFormatter {
	public function __construct(
        private GroupsWorkspaceService $groupsWorkspace,
        private ConnectedGroupsService $connectedGroupsService,
        private IURLGenerator $urlGenerator) {
	}

	/**
	 * @param IUser[] $users
	 */
	public function formatUsers(array $users, array $groupfolder, string $spaceId): array {
		$groupWorkspaceManager = $this->groupsWorkspace->getWorkspaceManagerGroup($spaceId);

		$usersFormatted = [];
		foreach ($users as $user) {
			if ($groupWorkspaceManager->inGroup($user)) {
				$role = Roles::Admin;
			} else {
				$role = Roles::User;
			}

			$usersFormatted[$user->getUID()] = [
				'uid' => $user->getUID(),
				'name' => $user->getDisplayName(),
				'email' => $user->getEmailAddress(),
				'subtitle' => $user->getEmailAddress(),
				'groups' => $this->groupsWorkspace->getGroupsUserFromGroupfolder($user, $groupfolder, $spaceId),
                'is_connected' => $this->connectedGroupsService->isUserConnectedGroup($user->getUID()),
                'profile' => $this->urlGenerator->linkToRouteAbsolute('core.ProfilePage.index', ['targetUserId' => $user->getUID()]),
                'role' => $role
			];
		}

		return $usersFormatted;
	}
}
