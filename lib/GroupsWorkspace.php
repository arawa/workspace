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

abstract class GroupsWorkspace {
	private const SPACE_MANAGER = 'GE-';
    private const SPACE_USERS = 'U-';
    private const GID_SPACE = 'SPACE-';

    protected const GID_MANAGERS = self::GID_SPACE . self::SPACE_MANAGER;
    protected const GID_USERS = self::GID_SPACE . self::SPACE_USERS;

	public const SPACE_WORKSPACE_MANAGER = 'WM-';
	public const USER_GROUP = 'Users-';

    abstract public static function get(int $spaceId): string;
    abstract public static function getPrefix(): string;

}
