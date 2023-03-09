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

namespace OCA\Workspace;

use OCA\Workspace\Db\Space;
use OCP\IGroup;

abstract class GroupsWorkspace {
	private const GID_SPACE_MANAGER = 'GE-';
    private const GID_SPACE_USERS = 'U-';
    private const GID_SPACE = 'SPACE-';

    protected const PREFIX_GID_MANAGERS = self::GID_SPACE . self::GID_SPACE_MANAGER;
    protected const PREFIX_GID_USERS = self::GID_SPACE . self::GID_SPACE_USERS;

	public const DISPLAY_PREFIX_MANAGER_GROUP = 'WM-';
	public const DISPLAY_PREFIX_USER_GROUP = 'Users-';

    abstract public static function get(int $spaceId): string;
    abstract public static function getPrefix(): string;
    abstract public function create(Space $space): IGroup;

}
