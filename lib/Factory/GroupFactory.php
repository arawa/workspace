<?php

namespace OCA\Workspace\Factory;

use OCA\Workspace\Db\Group;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Slugger;
use OCP\IGroup;

class GroupFactory {
	public function __construct() {
		
	}

	public static function createGroup(IGroup $ncGroup): Group {
		$group = new Group();
		$group->setGid($ncGroup->getGID());
		$group->setDisplayName($ncGroup->getDisplayName());
		$group->setTypes($ncGroup->getBackendNames());
		$group->setSlug(Slugger::slugger($ncGroup->getGID()));

		if (UserGroup::isWorkspaceGroup($ncGroup)) {
			$group->setUsersCount($ncGroup->count());
		} else {
			$users = $ncGroup->getUsers();
			$users = array_filter($users, fn ($user) => $user->isEnabled());
			$group->setUsersCount(count($users));
		}

		return $group;
	}
}
