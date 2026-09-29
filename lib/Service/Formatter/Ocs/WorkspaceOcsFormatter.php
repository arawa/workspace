<?php

namespace OCA\Workspace\Service\Formatter\Ocs;

use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;

/**
 * Formats a workspace for the public OCS API (WorkspaceApiOcsController).
 *
 * Not the shape used by the Vue front: see WorkspaceFormatter.
 *
 * The groupfolder array is passed through as is, so every key it holds
 * reaches the API, and that set of keys depends on the groupfolders version
 * (e.g. `team_circle_id` only exists on recent ones). The workspace row wins
 * over the groupfolder on conflicting keys (`id` is the workspace id).
 *
 * @psalm-import-type FormattedGroup from GroupFormatter
 */
class WorkspaceOcsFormatter {
	public function __construct(
		private WorkspaceGroupsResolver $groupsResolver,
	) {
	}

	/**
	 * @param array{id: int, groupfolder_id: int, name: string, color_code: string} $workspace a row of the work_spaces table
	 * @param array|null $folderInfo the groupfolder, as returned by FolderWithMappingsAndCache::toArray(),
	 *                               null when it cannot be loaded
	 * @return array{
	 *     id: int,
	 *     groupfolder_id: int,
	 *     name: string,
	 *     color_code: string,
	 *     mount_point?: string,
	 *     quota?: int,
	 *     acl?: bool,
	 *     acl_default_no_permission?: bool,
	 *     storage_id?: int,
	 *     root_id?: int,
	 *     root_cache_entry?: \OCP\Files\Cache\ICacheEntry,
	 *     manage?: list<array{type: string, id: string, displayname: string}>,
	 *     options?: array<string, mixed>,
	 *     team_circle_id?: ?string,
	 *     usersCount?: int,
	 *     groups: array<string, FormattedGroup>,
	 *     added_groups: \stdClass,
	 * } The `?` keys come from the groupfolder: they are missing when it cannot be loaded.
	 *   `usersCount` is missing when the SPACE-U group does not exist.
	 *   `added_groups` is an object keyed by gid holding FormattedGroup values,
	 *   so that it serializes as `{}` and not `[]` when empty.
	 */
	public function format(array $workspace, ?array $folderInfo): array {
		$space = !is_null($folderInfo)
			? array_merge($folderInfo, $workspace)
			: $workspace;

		$groups = $this->groupsResolver->resolve(array_keys($space['groups'] ?? []));

		if (!is_null($groups['userGroup'])) {
			$space['usersCount'] = $groups['userGroup']->count();
		}

		$space['groups'] = GroupFormatter::formatGroups($groups['workspaceGroups']);
		$space['added_groups'] = (object)GroupFormatter::formatGroups($groups['addedGroups']);

		return $space;
	}
}
