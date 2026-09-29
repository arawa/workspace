<?php

namespace OCA\Workspace\Service\Formatter;

use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;
use OCA\Workspace\Service\UserService;

/**
 * Formats a workspace for the Vue front (routes of appinfo/routes.php).
 *
 * Not the shape of the public OCS API: see Ocs\WorkspaceOcsFormatter.
 *
 * @psalm-import-type FormattedGroup from GroupFormatter
 */
class WorkspaceFormatter {

	public const NO_USERS = 0;

	public function __construct(
		private WorkspaceGroupsResolver $groupsResolver,
		private UserService $userService,
	) {
	}

	/**
	 * @param array{id: int, groupfolder_id: int, name: string, color_code: string} $workspace a row of the work_spaces table
	 * @param array $folderInfo the groupfolder, as returned by FolderWithMappingsAndCache::toArray()
	 * @return array{
	 *     id: ?int,
	 *     color: ?string,
	 *     groupfolderId: ?int,
	 *     isOpen: false,
	 *     name: ?string,
	 *     quota: ?int,
	 *     size: ?int,
	 *     managers: null,
	 *     users: \stdClass,
	 *     usersCount: int,
	 *     currentUserIsSimpleUser: bool,
	 *     groups: array<string, FormattedGroup>,
	 *     added_groups: \stdClass,
	 * } `added_groups` is an object keyed by gid holding FormattedGroup values,
	 *   so that it serializes as `{}` and not `[]` when empty
	 */
	public function format(array $workspace, array $folderInfo): array {
		$space = [
			'id' => $workspace['id'] ?? null,
			'color' => $workspace['color_code'] ?? null,
			'groupfolderId' => $workspace['groupfolder_id'] ?? null,
			'isOpen' => false,
			'name' => $workspace['name'] ?? null,
			'quota' => $folderInfo['quota'] ?? null,
			'size' => $folderInfo['root_cache_entry']?->getSize() ?? null,
			'managers' => null,
			'users' => (object)[],
			'usersCount' => self::NO_USERS,
			'currentUserIsSimpleUser' => $this->userService->isSimpleUserOfSpace($workspace),
		];

		$groups = $this->groupsResolver->resolve(array_keys($folderInfo['groups'] ?? []));

		if (!is_null($groups['userGroup'])) {
			$space['usersCount'] = $groups['userGroup']->count();
		}

		$space['groups'] = GroupFormatter::formatGroups($groups['workspaceGroups']);
		$space['added_groups'] = (object)GroupFormatter::formatGroups($groups['addedGroups']);

		return $space;
	}
}
