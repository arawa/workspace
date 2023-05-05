/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license AGPL-3.0-or-later
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
import { ESPACE_GID_PREFIX, ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX } from '../constants.js'
import { generateUrl } from '@nextcloud/router'
import BadCreateError from '../Errors/BadCreateError.js'
import showNotificationError from './Notifications/NotificationError.js'

/**
	* @param {string} spaceName it's a name for the space to create
	* @param {number} folderId it's the id of groupfolder
	* @param {object} vueInstance it's an instance of vue
	* @return {object}
	*/
export function createSpace(spaceName, folderId, vueInstance = undefined) {
	const result = axios.post(generateUrl('/apps/workspace/spaces'),
		{
			spaceName,
			folderId,
		})
		.then(resp => {
			if (typeof (resp.data) !== 'object') {
				throw new Error('Error when creating a space, it\'s not an object type.')
			}

			return resp.data
		})
		.catch(error => {
			if (typeof (vueInstance) !== 'undefined') {
        showNotificationError('Error to create a workspace', error.message)
			}
			throw new BadCreateError(error)
		})
	return result
}

/**
	* @param {string} spacename it's the name of the space which will create
	* @return {string}
	*/
export function deleteBlankSpacename(spacename) {
	return spacename.trim()
}

/**
 * @param {string} spaceId - workspace id as identifier
 * @param {object} groupfolder - groupfolder object
 * @return {object}
 */
export function transferUsersToUserGroup(spaceId, groupfolder) {
	const result = axios.post(generateUrl(`/apps/workspace/spaces/${spaceId}/transfer-users`),
		{
			groupfolder,
		})
		.then(resp => {
			return resp.data
		})
		.catch(error => {
			console.error('Error to transfer users', error)
		})
	return result
}

/**
 * @param {string} group it's the groupname to test
 * @return {boolean}
 */
export function isSpaceManagers(group) {
	const SPACE_MANAGER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX)
	return SPACE_MANAGER_REGEX.test(group)
}

/**
	* @param {string} group it's the groupname to test
	* @return {boolean}
	*/
export function isSpaceUsers(group) {
	const SPACE_USER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX)
	return SPACE_USER_REGEX.test(group)
}
