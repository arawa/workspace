<?php

namespace OCA\Workspace\Service\Formatter\Ocs;

use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;

/**
 * Formats a workspace for the public OCS API (WorkspaceApiOcsController).
 *
 * The output is the WorkspaceSpace type of ResponseDefinitions, published in
 * openapi.json: only the documented keys are returned, so the API does not
 * depend on which keys the groupfolders version puts in its folder array.
 * Adding a key here means documenting it in ResponseDefinitions too.
 *
 * @psalm-import-type FormattedGroup from GroupFormatter
 */
class WorkspaceOcsFormatter {
	public const NO_USERS = 0;

	public function __construct(
		private WorkspaceGroupsResolver $groupsResolver,
	) {
	}

	/**
	 * @param array{id: int, groupfolder_id: int, name: string, color_code: string} $workspace a row of the work_spaces table
	 * @param array $folderInfo the groupfolder, as returned by FolderWithMappingsAndCache::toArray()
	 * @return array{
	 *     id: int,
	 *     mount_point: string,
	 *     groups: \stdClass,
	 *     quota: int,
	 *     size: int,
	 *     acl: bool,
	 *     manage: list<array{type: string, id: string, displayname: string}>,
	 *     groupfolder_id: int,
	 *     name: string,
	 *     color_code: string,
	 *     usersCount: int,
	 *     added_groups: \stdClass,
	 * } `groups` and `added_groups` are objects keyed by gid holding FormattedGroup values,
	 *   so that they serialize as `{}` and not `[]` when empty
	 */
	public function format(array $workspace, array $folderInfo): array {
		$groups = $this->groupsResolver->resolve(array_keys($folderInfo['groups'] ?? []));

		return [
			'id' => $workspace['id'],
			'mount_point' => $folderInfo['mount_point'],
			'groups' => (object)GroupFormatter::formatGroups($groups['workspaceGroups']),
			'quota' => $folderInfo['quota'],
			'size' => $folderInfo['root_cache_entry']->getSize(),
			'acl' => $folderInfo['acl'],
			'manage' => $folderInfo['manage'],
			'groupfolder_id' => $workspace['groupfolder_id'],
			'name' => $workspace['name'],
			'color_code' => $workspace['color_code'],
			'usersCount' => $groups['userGroup']?->count() ?? self::NO_USERS,
			'added_groups' => (object)GroupFormatter::formatGroups($groups['addedGroups']),
		];
	}
}
