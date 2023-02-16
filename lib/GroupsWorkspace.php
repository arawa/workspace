<?php

namespace OCA\Workspace;

class GroupsWorkspace {
	public const SPACE_MANAGER = 'GE-';
	public const SPACE_WORKSPACE_MANAGER = 'WM-';
    public const SPACE_USERS = 'U-';
    public const GID_SPACE = 'SPACE-';
	public const USER_GROUP = 'Users-';

	public static function getUserGroup(array $workspace): string
	{
		$groups = array_keys($workspace['groups']);
		$regex = '/^' . self::GID_SPACE . self::SPACE_USERS . '[0-9]/';
		foreach ($groups as $group)
		{
			if (preg_match($regex, $group))
			{
				return self::GID_SPACE . self::SPACE_USERS . $workspace['id'];
			}
		}

		return self::GID_SPACE . self::SPACE_USERS . $workspace['name'];
	}

	public static function getWorkspacesManagersGroup(array $workspace): string
	{
		$groups = array_keys($workspace['groups']);

		$regex = '/^' . self::GID_SPACE . self::SPACE_MANAGER . '[0-9]/';
		foreach ($groups as $group)
		{
			if (preg_match($regex, $group))
			{
				return self::GID_SPACE . self::SPACE_MANAGER . $workspace['id'];
			}
		}

		return self::GID_SPACE . self::SPACE_WORKSPACE_MANAGER . $workspace['name'];
	}
}
