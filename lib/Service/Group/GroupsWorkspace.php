<?php
/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Service\Group;

use OCA\Workspace\Db\Space;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroup;

abstract class GroupsWorkspace {
	private const GID_SPACE_MANAGER = 'GE-';
	private const GID_SPACE_USERS = 'U-';
	private const GID_SPACE = 'SPACE-';
	public const DEFAULT_DISPLAY_PREFIX_MANAGER_GROUP = 'WM-';
	public const DEFAULT_DISPLAY_PREFIX_USER_GROUP = 'U-';
	public const LEGACY_DISPLAY_PREFIX_LOCAL_GROUP = 'G-';

	protected const PREFIX_GID_MANAGERS = self::GID_SPACE . self::GID_SPACE_MANAGER;
	protected const PREFIX_GID_USERS = self::GID_SPACE . self::GID_SPACE_USERS;

	protected static string $DISPLAY_PREFIX_MANAGER_GROUP;
	protected static string $DISPLAY_PREFIX_USER_GROUP;

	public function __construct(IAppConfig $appConfig) {
		self::$DISPLAY_PREFIX_MANAGER_GROUP = $appConfig->getAppValue('DISPLAY_PREFIX_MANAGER_GROUP', self::DEFAULT_DISPLAY_PREFIX_MANAGER_GROUP);
		self::$DISPLAY_PREFIX_USER_GROUP = $appConfig->getAppValue('DISPLAY_PREFIX_USER_GROUP', self::DEFAULT_DISPLAY_PREFIX_USER_GROUP);
	}

	public static function getDisplayPrefixManagerGroup(): string {
		return self::$DISPLAY_PREFIX_MANAGER_GROUP;
	}

	public static function getDisplayPrefixUserGroup(): string {
		return self::$DISPLAY_PREFIX_USER_GROUP;
	}

	public static function isWorkspaceUserGroupId(string $gid): bool {
		return str_starts_with($gid, self::PREFIX_GID_USERS);
	}

	public static function isWorkspaceAdminGroupId(string $gid): bool {
		return str_starts_with($gid, self::PREFIX_GID_MANAGERS);
	}

	public static function isWorkspaceGroup(IGroup $group) {
		return str_starts_with($group->getGID(), self::GID_SPACE)
			|| str_starts_with($group->getDisplayName(), self::LEGACY_DISPLAY_PREFIX_LOCAL_GROUP);
	}

	/**
	 * @return string - Just the GID with the spaceId.
	 */
	abstract public static function get(int $spaceId): string;

	abstract public static function getPrefix(): string;

	/**
	 * Use the OCA\Workspace\Db\Space to get its spaceId and spaceName.
	 */
	abstract public function create(Space $space): IGroup;
}
