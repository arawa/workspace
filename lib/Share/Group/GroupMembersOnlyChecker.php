<?php

namespace OCA\Workspace\Share\Group;

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Share\IManager;

class GroupMembersOnlyChecker {
	public function __construct(
		private IGroupManager $groupManager,
		private IManager $shareManager,
		private IUserSession $userSession
	) {
	}

	public function checkboxIsChecked(): bool {
		return $this->shareManager->shareWithGroupMembersOnly();
	}

	public function groupsExcludeSelected(): bool {
		if (
			method_exists(
				$this->shareManager,
				"shareWithGroupMembersOnlyExcludeGroupsList"
			)
		) {
			return $this->shareManager->shareWithGroupMembersOnly()
				&& !empty($this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList());
		}

		return false;
	}

	public function checkMemberInGroupExcluded(): bool {
		$excludedGroups = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();

		$userSession = $this->userSession->getUser();
		$uid = $userSession->getUID();

		foreach ($excludedGroups as $gid) {
			if ($this->groupManager->isInGroup($uid, $gid)) {
				return true;
			}
		}

		return false;
	}
}
