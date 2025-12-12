/**
 * copyright Copyright (c) 2017 Arawa
 *
 * author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * license GNU AGPL version 3 or any later version
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

const ESPACE_MANAGERS = 'GE-'
const ESPACE_USERS = 'U-'
const ESPACE_GID = 'SPACE-'
const ESPACE_GROUP = 'G-'
export const PREFIX_USER = ESPACE_GID + ESPACE_USERS
export const PREFIX_MANAGER = ESPACE_GID + ESPACE_MANAGERS
export const PREFIX_GID_SUBGROUP_SPACE = ESPACE_GID + ESPACE_GROUP
export const PREFIX_DISPLAYNAME_SUBGROUP_SPACE = ESPACE_GROUP
export const LIMIT_WORKSPACES_PER_PAGE = 25
