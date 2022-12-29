<?php

namespace OCA\Workspace\Service\Group\GroupFolder;

class GroupFolderManage
{
	public static function filterGroup(array $groupfolder): array
	{
		$groupsManageFiltered = array_filter(
			$groupfolder['manage'],
			function ($object) {
				if ($object['type'] === 'group') {
					return true;
				}
			}
		);

		$idGroupsManage = [];
		foreach ($groupsManageFiltered as $groupManage)
		{
			$idGroupsManage[] = $groupManage['id'];
		}

		return $idGroupsManage;
	}
}
