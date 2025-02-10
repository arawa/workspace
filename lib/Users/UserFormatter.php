<?php

namespace OCA\Workspace\Users;

use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\UserGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;

class UserFormatter {
	public function __construct(
		private IGroupManager $groupManager,
		private UserGroup $userGroup,
		private ConnectedGroupsService $connectedGroupsService,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @since 3.1.0|4.0.0
	 *
	 * @param IUser $user
	 * @param array $space
	 * @param string $role admin|user
	 * @return array users array valid for the frontend
	 */
	public function formatUser(IUser $user, array $space, string $role): array {
		// Gets the workspace subgroups the user is member of
		$groups = [];
		foreach ($this->groupManager->getUserGroups($user) as $group) {
			if (in_array($group->getGID(), array_keys($space['groups']))) {
				array_push($groups, $group->getGID());
			}
		}

		return [
			'uid' => $user->getUID(),
			'name' => $user->getDisplayName(),
			'email' => $user->getEmailAddress(),
			'subtitle' => $user->getEmailAddress(),
			'groups' => $groups,
			'is_connected' => $this->connectedGroupsService->isUserConnectedGroup($user->getUID(), $space),
			'profile' => $this->urlGenerator->linkToRouteAbsolute('core.ProfilePage.index', ['targetUserId' => $user->getUID()]),
			'role' => $role
		];
	}
}
