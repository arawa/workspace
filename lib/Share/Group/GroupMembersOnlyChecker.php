<?php

namespace OCA\Workspace\Share\Group;

use OCP\Share\IManager;

class GroupMembersOnlyChecker {
	public function __construct(
		private IManager $shareManager
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
}
