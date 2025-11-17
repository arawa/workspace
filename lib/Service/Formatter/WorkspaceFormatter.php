<?php

namespace OCA\Workspace\Service\Formatter;

use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

class WorkspaceFormatter {

	public const NO_USERS = 0;

	public function __construct(
		private LoggerInterface $logger,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * @param array $workspace
	 * @param array $folderInfo
	 * @return array
	 */
	public function format(array $workspace, array $folderInfo): array {
		$space = [
			'id' => $workspace['id'] ?? null,
			'color' => $workspace['color_code'] ?? null,
			'groupfolderId' => $workspace['groupfolder_id'] ?? null,
			'isOpen' => false,
			'name' => $workspace['name'] ?? null,
			'quota' => $folderInfo['quota'] ?? null,
			'size' => $folderInfo['size'] ?? null,
			'managers' => null,
			'users' => (object)[],
			'usersCount' => self::NO_USERS,
		];

		$wsGroups = [];
		$addedGroups = [];
		$gids = array_keys($folderInfo['groups'] ?? []);

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
				$wsGroups[] = $group;
			} else {
				$addedGroups[] = $group;
			}

			if (UserGroup::isWorkspaceUserGroupId($gid)) {
				$space['usersCount'] = $group->count();
			}
		}

		$space['groups'] = GroupFormatter::formatGroups($wsGroups);
		$space['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

		return $space;
	}
}
