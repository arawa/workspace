<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2025 Arawa
 *
 * @author 2025 Sébastien Marinier <seb@smarinier.net>
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

/**
 * @psalm-type WorkspaceCapabilities = array{
 *		version: string,
 *		is_enabled: bool,
 * 		groupfolders_version: string,
 *		groupfolders_acl-inherit-per-user: string,
 * }
 *
 * @psalm-type WorkspaceSpace = array{
 *		id: int,
 * 		mount_point: string,
 * 		groups: list<array{}>,
 * 		quota: int,
 * 		size: int,
 * 		acl: bool,
 * 		manage: list<array{}>,
 * 		groupfolder_id: int,
 * 		name: string,
 * 		color_code: string,
 * 		userCount: int,
 * 		users: list<array{}>,
 * 		added_groups: list<array{}>
 * }
 * 
 * @psalm-type WorkspaceGroupInfo = array{
 * 		gid: string,
 * 		displayName: string,
 * 		types: list<string>,
 * 		usersCount: int,
 * 		slug: string
 * }
 * 
 * @psalm-type WorkspaceFindGroups = array<string, WorkspaceGroupInfo>
 *
 * @psalm-type WorkspaceSpaceDelete = array{
 * 		name: string,
 * 		groups: list<string>,
 * 		id: int,
 * 		groupfolder_id: int,
 * 		state: string
 * }
 * 
 * @psalm-type WorkspaceConfirmationMessage = array{
 * 		message: string
 * }
 *
 */
class ResponseDefinitions {
}
