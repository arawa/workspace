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
import { PREFIX_MANAGER, PREFIX_USER } from '../constants.js'
import { generateUrl } from '@nextcloud/router'
import BadCreateError from '../Errors/BadCreateError.js'
import showNotificationError from './Notifications/NotificationError.js'
import AddGroupToGroupfolderError from '../Errors/Groupfolders/AddGroupToGroupfolderError.js'

/**
	* @param {string} spaceName it's a name for the space to create
	* @param {number} folderId it's the id of groupfolder
	* @param {object} vueInstance it's an instance of vue
	* @return {object}
	*/
export function createSpace(spaceName, vueInstance = undefined) {
	const result = axios.post(generateUrl('/apps/workspace/spaces'),
		{
			spaceName,
		})
		.then(resp => {
			if (typeof (resp.data) !== 'object') {
				throw new Error('Error when creating a space, it\'s not an object type.')
			}

			return resp.data
		})
		.catch(error => {
			if ('response' in error && 'data' in error.response) {
				showNotificationError(error.response.data.title, error.response.data.message, 5000)
			} else {
				showNotificationError('Error to create a workspace', error.message, 5000)
			}

			throw new BadCreateError(error)
		})
	return result
}

export function getUsers(spaceId) {
	const result = axios.get(generateUrl(`/apps/workspace/spaces/${spaceId}/users`))
		.then(resp => {
			return resp.data
		})
		.catch(error => {
			console.error('Impossible to get users from a workspace.', error)
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
	const OLD_SPACE_MANAGER_REGEX = new RegExp('^' + PREFIX_MANAGER)

	return OLD_SPACE_MANAGER_REGEX.test(group) === true

}

/**
	* @param {string} group it's the groupname to test
	* @return {boolean}
	*/
export function isSpaceUsers(group) {
	const SPACE_USER_REGEX = new RegExp('^' + PREFIX_USER)
	return SPACE_USER_REGEX.test(group)
}

/**
 * @param {number} spaceId of a workspace
 * @param {string} gid it's an id (string format) of a group
 * @return {Promise}
 * @throws {AddGroupToGroupfolderError}
 */
export function addGroupToWorkspace(spaceId, gid) {
	return axios.post(generateUrl(`/apps/workspace/spaces/${spaceId}/group-attach`), {
		gid,
	})
		.then(resp => {
			return resp.data
		})
		.catch(error => {
			showNotificationError(
				'Error groups',
				`Impossible to attach the ${error} group to workspace. May be a problem with the connection ?`,
				5000)
			console.error('Impossible to attach the group to workspace. May be a problem with the connection ?', gid, error)
			throw new AddGroupToGroupfolderError('Error to add Space Manager group in the groupfolder')
		})
}

/**
 * @param {integer} spaceId it's the id relative to workspace
 * @return {Promise}
 */
export function removeWorkspace(spaceId) {
	const result = axios.delete(generateUrl(`/apps/workspace/spaces/${spaceId}`))
		.then(resp => {
			console.info(`The workspace with the ${spaceId} id, is deleted.`)
			return resp.data
		})
		.catch(error => {
			console.error('Error to delete a workspace. May be a problem network ?', error)
		})
	return result
}

export function renameSpace(spaceId, newSpaceName) {
	const respFormat = {
		data: {},
	}
	respFormat.data.statuscode = 500
	respFormat.data.message = 'Rename the space is impossible.'

	newSpaceName = deleteBlankSpacename(newSpaceName)

	const respFormatFinal = axios.patch(generateUrl(`/apps/workspace/spaces/${spaceId}`),
		{
			newSpaceName,
		})
		.then(resp => {
			if (resp.data.statuscode === 400) {
				respFormat.data.statuscode = 400
				respFormat.data.space = null
				return respFormat
			}

			if (resp.data.statuscode === 204) {
				respFormat.data.statuscode = 204
				respFormat.data.space = newSpaceName
				return respFormat
			}

			return respFormat
		})
		.catch(error => {
			if ('response' in error && 'data' in error.response) {
				showNotificationError(error.response.data.title, error.response.data.message, 5000)
				throw new Error(error.response.data.message)
			} else {
				showNotificationError('Error to rename a workspace', error.message, 5000)
				console.error('Problem to rename the space', error)
				throw new Error(error.message)
			}
		})

	return respFormatFinal
}
