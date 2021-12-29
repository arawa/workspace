/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

import axios from '@nextcloud/axios'
import { ESPACE_GID_PREFIX, ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX } from '../constants'
import { generateUrl } from '@nextcloud/router'

// Param: string spaceName
// Param: int folderId
// return object
export function createSpace(spaceName, folderId) {
	const result = axios.post(generateUrl('/apps/workspace/spaces'),
		{
			spaceName,
			folderId,
		})
		.then(resp => {
			return resp.data
		})
		.catch(error => {
			console.error('createSpace error', error)
		})
	return result
}

// Param string group
// return string
export function isSpaceManagers(group) {
	const SPACE_MANAGER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX)
	return SPACE_MANAGER_REGEX.test(group)
}

// Param string group
// return string
export function isSpaceUsers(group) {
	const SPACE_USER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX)
	return SPACE_USER_REGEX.test(group)
}
