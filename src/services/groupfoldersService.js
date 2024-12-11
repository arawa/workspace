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

import { deleteBlankSpacename } from './spaceService.js'
import { generateUrl } from '@nextcloud/router'
import AddGroupToGroupfolderError from '../Errors/Groupfolders/AddGroupToGroupfolderError.js'
import AddGroupToManageACLForGroupfolderError from '../Errors/Groupfolders/AddGroupToManageACLForGroupfolderError.js'
import axios from '@nextcloud/axios'
import BadGetError from '../Errors/BadGetError.js'
import CheckGroupfolderNameExistError from '../Errors/Groupfolders/CheckGroupfolderNameError.js'
import CreateGroupfolderError from '../Errors/Groupfolders/BadCreateError.js'
import EnableAclGroupfolderError from '../Errors/Groupfolders/EnableAclGroupfolderError.js'
import showNotificationError from './Notifications/NotificationError.js'
import RemoveGroupToManageACLForGroupfolderError from '../Errors/Groupfolders/RemoveGroupToManageACLForGroupfolderError.js'

/**
 * @return {object}
 */
export function getAll() {
	const data = axios.get(generateUrl('/apps/groupfolders/folders'))
		.then(resp => {
			if (resp.data.ocs.meta.status === 'ok') {
				return resp.data.ocs.data
			}
			throw new Error()
		})
		.catch(error => {
			throw new BadGetError('Error to get all spaces', error.reason)
		})
	return data
}

/**
 *
 * @param {object} space it's an object relative to space
 * @return {Promise}
 */
export function formatGroups(space) {
	const data = axios.post(generateUrl('/apps/workspace/api/workspace/formatGroups'), { workspace: space })
		.then(resp => {
			return resp
		})
		.catch(error => {
			console.error('Error POST to format space\'s groups', error)
		})
	return data
}

/**
 *
 * @param {object} space it's an object relative to space
 * @return {Promise}
 */
export function formatUsers(space) {
	const data = axios.post(generateUrl('/apps/workspace/api/workspace/formatUsers'), { workspace: space })
		.then(resp => {
			return resp
		})
		.catch(error => {
			console.error('Error POST to format space\'s users', error)
		})
	return data
}

/**
 * @param {string} spaceName it's the name of space to check
 * @return {boolean}
 * @throws {CheckGroupfolderNameExistError}
 * @deprecated
 * @use createSpace from spaceService
 */
export async function checkGroupfolderNameExist(spaceName) {
	const duplicateExists = await getAll()
		.then(groupfolders => {
			for (const folderId in groupfolders) {
				if (spaceName.toLowerCase() === groupfolders[folderId].mount_point.toLowerCase()) {
					return true
				}
			}
			return false
		})
		.catch((error) => {
			console.error(error)
		})

	if (duplicateExists) {
		showNotificationError('Error - Duplicate space name', 'This space or groupfolder already exist. Please, input another space.\nIf "toto" space exist, you cannot create the "tOTo" space.\nMake sure you the groupfolder doesn\'t exist.', 5000)
		throw new CheckGroupfolderNameExistError('This space or groupfolder already exist. Please, input another space.'
		+ '\nIf "toto" space exist, you cannot create the "tOTo" space.'
		+ '\nMake sure you the groupfolder doesn\'t exist.', 5000)
	}
	return false
}

/**
 * @param {number} folderId from a groupfolder
 * @return {Promise}
 * @throws {EnableAclGroupfolderError}
 * @deprecated
 * @use createSpace from spaceService
 */
export function enableAcl(folderId) {
	return axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/acl`),
		{
			acl: 1,
		})
		.then(resp => {
			if (resp.status === 200 && resp.data.ocs.meta.status === 'ok') {
				return resp.data.ocs.data
			}

			if (resp.status === 500) {
				throw new Error('Groupfolders\' API doesn\'t enable ACL. May be a problem with the connection ?')
			}
		})
		.catch(error => {
			throw new EnableAclGroupfolderError(error.message)
		})
}

/**
 * @param {number} folderId of an groupfolder
 * @param {string} gid it's an id (string format) of a group
 * @return {Promise}
 * @throws {AddGroupToGroupfolderError}
 * @deprecated
 * @use createSpace from spaceService
 */
export function addGroupToGroupfolder(folderId, gid) {
	return axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/groups`),
		{
			group: gid,
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			showNotificationError(
				'Error groups',
				`Impossible to attach the ${error} group to groupfolder. May be a problem with the connection ?`,
				5000)
			console.error(`Impossible to attach the ${gid} group to groupfolder. May be a problem with the connection ?`, error)
			throw new AddGroupToGroupfolderError('Error to add Space Manager group in the groupfolder')
		})
}

/**
 * @param {number} folderId it's an id of a groupfolder
 * @param {string} gid it's an id (string format) of a group
 * @return {Promise}
 * @throws {AddGroupToManageACLForGroupfolderError}
 * @deprecated
 * @use createSpace from spaceService
 */
export function addGroupToManageACLForGroupfolder(folderId, gid) {
	return axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/manageACL`),
		{
			mappingType: 'group',
			mappingId: gid,
			manageAcl: true,
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			showNotificationError(
				'Error to add group as manager acl',
				'Impossible to add the Space Manager group in Manage ACL groupfolder',
				5000)
			console.error('Impossible to add the Space Manager group in Manage ACL groupfolder', error)
			throw new AddGroupToManageACLForGroupfolderError('Error to add the Space Manager group in manage ACL groupfolder')
		})
}

/**
 * @param {number} folderId it's an id of a groupfolder
 * @param {string} gid it's an id (string format) of a group
 * @return {Promise}
 * @throws {RemoveGroupToManageACLForGroupfolderError}
 */
export function removeGroupToManageACLForGroupfolder(folderId, gid) {
	return axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/manageACL`),
		{
			mappingType: 'group',
			mappingId: gid,
			manageAcl: false,
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			showNotificationError(
				'Error to remove group as manager acl',
				'Impossible to remove the group from the advanced permissions.',
				5000)
			console.error('Impossible to remove the group from the advanced permissions.', error)
			throw new RemoveGroupToManageACLForGroupfolderError('Impossible to remove the group from the advanced permissions.')
		})
}

/**
 * @param {string} spaceName it's the name space to create
 * @return {Promise}
 * @throws {CreateGroupfolderError}
 * @deprecated
 * @use createSpace from spaceService
 */
export function createGroupfolder(spaceName) {
	return axios.post(generateUrl('/apps/groupfolders/folders'),
		{
			mountpoint: spaceName,
		})
		.then(resp => {
			if (resp.data.ocs.meta.statuscode !== 100) {
				throw new Error('Impossible to create a groupfolder. May be an error network ?')
			}
			return resp.data.ocs
		})
		.catch(error => {
			showNotificationError('Error - Creating space', error.message, 5000)
			throw new CreateGroupfolderError('Network error - the error is: ' + error.message)
		})
}

/**
 * @param {object} workspace it's an object relative to workspace
 * @deprecated
 * @return {Promise}
 */
export function destroy(workspace) {
	// It's possible to send data with the DELETE verb adding `data` key word as
	// second argument in the `delete` method.
	const spaceId = workspace.id
	const result = axios.delete(generateUrl(`/apps/workspace/spaces/${spaceId}`),
		{
			data: {
				workspace,
			},
		})
		.then(resp => {
			if (resp.status === 200) {
				// delete groupfolders
				axios.delete(generateUrl(`/apps/groupfolders/folders/${workspace.groupfolderId}`))
					.then(resp => {
						if (!resp.data.ocs.meta.status === 'ok') {
							console.error('Error to delete this groupfolder', workspace)
						}
					})
					.catch(error => {
						console.error('Error to delete a groupfolder. May be a problem network ?', error)
					})
			}
			return resp.data
		})
	return result
}

/**
 *
 * @param {object} workspace it's the object relative to workspace
 * @param {string} newSpaceName it's the new name for the workspace
 * @return {Promise}
 * @deprecated
 */
export function rename(workspace, newSpaceName) {
	// Response format to return
	const respFormat = {
		data: {},
	}
	respFormat.data.statuscode = 500
	respFormat.data.message = 'Rename the space is impossible.'

	newSpaceName = deleteBlankSpacename(newSpaceName)
	// Update space side
	const workspaceUpdated = axios.patch(generateUrl('/apps/workspace/api/space/rename'),
		{
			workspace,
			newSpaceName,
			spaceId: workspace.id
		})
		.then(resp => {
			// If space is updated...
			if (resp.data.statuscode === 204) {
				const space = resp.data.space
				// ... the groupfolder is updating
				const groupfolderUpdated = axios.post(generateUrl(`/apps/groupfolders/folders/${space.groupfolder_id}/mountpoint`),
					{
						mountpoint: space.name,
					})
					.then(resp => {
						return resp
					})
					.catch(error => {
						console.error('Error to call Groupfolder\'s API', error)
					})
				return groupfolderUpdated
			}

			if (resp.data.statuscode === 400) {
				respFormat.data.statuscode = 400
				respFormat.data.space = null
				respFormat.data.groups = null
				respFormat.data.message = resp.data.message
				return respFormat
			}
		})
		.catch(error => {
			console.error('Problem to rename the space', error)
		})
	const respFormatFinal = workspaceUpdated
		.then(resultat => {

			if (!Object.prototype.hasOwnProperty.call(resultat.data, 'ocs')) {
				if (resultat.data.statuscode === 400) {
					return resultat
				}
			}

			if (resultat.data.ocs.data.success) {
				respFormat.data.statuscode = 204
				respFormat.data.space = newSpaceName
				respFormat.data.groups = workspace.groups
				respFormat.data.message = 'Space and Groupfolder are updated both side.'
				return respFormat
			}
		})
		.catch(error => {
			console.error('Problem to format the object when renamed the space name', error)
		})
	return respFormatFinal
}
