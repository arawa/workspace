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
import { generateUrl } from '@nextcloud/router'
import { createSpace, deleteBlankSpacename, isSpaceManagers, isSpaceUsers } from './spaceService'

/**
 * @return {object}
 */
export function getAll() {
	const data = axios.get(generateUrl('/apps/groupfolders/folders'))
		.then(resp => {
			if (resp.data.ocs.meta.status === 'ok') {
				return resp.data.ocs.data
			}
		})
		.catch(e => {
			console.error('Error to get all spaces', e)
		})
	return data
}

/**
 *
 * @param {number} groupfolderId it's the id of a groupfolder
 * @return {object}
 */
export function get(groupfolderId) {
	const data = axios.get(generateUrl(`/apps/groupfolders/folders/${groupfolderId}`))
		.then(resp => {
			if (resp.data.ocs.meta.status === 'ok') {
				const workspace = resp.data.ocs.data
				return workspace
			}
		})
		.catch(e => {
			console.error('Error to get one space', e)
		})
	return data
}

/**
 *
 * @param {object} space it's an object relative to space
 * @return {object}
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
 * @return {object}
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
 * @return {boolean} true if such a groupfolder exists, false otherwise
 */
function checkGroupfolderNameExist(spaceName) {
	const groupfolders = getAll()
		.then(groupfolders => {
			for (const folderId in groupfolders) {
				if (spaceName.toLowerCase() === groupfolders[folderId].mount_point.toLowerCase()) {
					return true
				}
			}
			return false
		})
		.catch(error => {
			console.error('Cannot get all Groupfolders', error)
			return false
		})
	return groupfolders
}

/**
 * @param {number} folderId from a groupfolder
 * @return {object}
 */
export function enableAcl(folderId) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/acl`),
		{
			acl: 1,
		})
		.then(resp => {
			if (resp.status === 200 && resp.data.ocs.meta.status === 'ok') {
				return resp.data.ocs.data
			}
		})
		.catch(error => {
			console.error('Groupfolders\' API doesn\'t enable ACL. May be a problem with the connection ?', error)
		})
	return result
}

/**
 * @param {number} folderId of an groupfolder
 * @param {string} gid it's an id (string format) of a group
 * @return {object}
 */
export function addGroup(folderId, gid) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/groups`),
		{
			group: gid,
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			console.error(`Impossible to attach the ${gid} group to groupfolder. May be a problem with the connection ?`, error)
		})
	return result
}

/**
 * @param {number} folderId it's an id of a groupfolder
 * @param {string} gid it's an id (string format) of a group
 * @param {boolean} manageAcl (default: true)
 * @return {object} it's an object to check if it's a success or not
 */
export function manageACL(folderId, gid, manageAcl = true) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/manageACL`),
		{
			mappingType: 'group',
			mappingId: gid,
			manageAcl,
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			console.error('Impossible to add the Space Manager group in Manage ACL groupfolder', error)
		})
	return result
}

/**
 * @param {string} spaceName it's the name space to create
 * @return {object} data
 */
export async function create(spaceName) {
	const data = { }
	data.data = { }
	const groupfolderIsExist = await checkGroupfolderNameExist(spaceName)
	// Todo: Should I return a boolean value ?
	if (groupfolderIsExist) {
		data.data.message = `The groupfolder with this name : "${spaceName}" already exist`
		data.data.statuscode = 400
		data.data.spacename = spaceName
		return data
	}

	// Create groupfolder
	const groupfolderId = await axios.post(generateUrl('/apps/groupfolders/folders'),
		{
			mountpoint: spaceName,
		})
		.then(resp => {
			return resp.data.ocs
		})
		.catch(error => {
			console.error('Impossible to create a groupfolder. May be an error network ?', error)
			data.data.statuscode = 400
			return data
		})
	if (groupfolderId.meta.statuscode !== 100) {
		console.error('Error when creating on groupfolder')
		data.data.statuscode = 500
		return data
	}

	// Get groupfolder created
	const groupfolder = await get(groupfolderId.data.id)
		.then(resp => {
			return resp
		})
		.catch(error => {
			console.error('Impossible to get the groupfolder. May be an error network ?', error)
			data.data.statuscode = 400
			return data
		})

	// Enable ACL on the groupfolder created
	const aclIsEnabled = await enableAcl(groupfolder.id)
	if (!aclIsEnabled.success) {
		console.error('Error to enable acl')
		data.data.statuscode = 500
		return data
	}
	// Create the space
	const resultCreateSpace = await createSpace(groupfolder.mount_point, groupfolder.id)
	if (typeof (resultCreateSpace) !== 'object') {
		console.error('Error when creating a space, it\'s not an object type.')
		data.data.statuscode = 500
		return data
	}
	// resultCreateSpace fill data
	data.data = resultCreateSpace

	// acl fill data
	data.data.acl = {}
	data.data.acl.state = true

	// Add groups to groupfolder
	const GROUPS = Object.keys(resultCreateSpace.groups)
	const spaceManagerGID = GROUPS.find(isSpaceManagers)
	const spaceUserGID = GROUPS.find(isSpaceUsers)

	const isAddGroupForSpaceManager = await addGroup(resultCreateSpace.folder_id, spaceManagerGID)
	if (!isAddGroupForSpaceManager.success) {
		console.error('Error to add Space Manager group in the groupfolder')
		data.data.statuscode = 500
		return data
	}

	const isAddGroupForSpaceUser = await addGroup(resultCreateSpace.folder_id, spaceUserGID)
	if (!isAddGroupForSpaceUser.success) {
		console.error('Error to add Space Users group in the groupfolder')
		data.data.statuscode = 500
		return data
	}

	// Add Space Manager group in manage ACL
	const resultManageACL = await manageACL(resultCreateSpace.folder_id, spaceManagerGID)
	if (!resultManageACL.success) {
		console.error('Error to add the Space Manager group in manage ACL')
		console.error('GroupFolder API to manage ACL a groupfolder doesn\'t respond')
		data.data.statuscode = 500
		return data
	}
	// resultManageACL fill data
	data.data.space_advanced_permissions = true
	data.data.assign_permission = {
		status: 'enabled',
		groups: [
			spaceManagerGID,
		],
	}

	return data
}

/**
 * @param {object} workspace it's an object relative to workspace
 * @return {object}
 */
export function destroy(workspace) {
	// It's possible to send data with the DELETE verb adding `data` key word as
	// second argument in the `delete` method.
	const result = axios.delete(generateUrl('/apps/workspace/api/delete/space'),
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
 * @return {object}
 */
export function rename(workspace, newSpaceName) {
	// Response format to return
	const respFormat = {
		data: {},
	}
	respFormat.data.statuscode = 500
	respFormat.data.message = 'Rename the space is impossible.'

	if (!checkGroupfolderNameExist(workspace.name)) {
		respFormat.data.statuscode = 409
		respFormat.data.message = 'The space name already exist. We cannot rename with this name.'
		console.error('The groupfolder name already exist. Please, choose another name to rename your space.')
		return respFormat
	}
	newSpaceName = deleteBlankSpacename(newSpaceName)
	// Update space side
	const workspaceUpdated = axios.patch(generateUrl('/apps/workspace/api/space/rename'),
		{
			workspace,
			newSpaceName,
		})
		.then(resp => {
			// If space is updated...
			if (resp.data.statuscode === 204) {
				const space = resp.data.space
				// ... the groupfolder is updating
				const groupfolderUpdated = axios.post(generateUrl(`/apps/groupfolders/folders/${space.groupfolder_id}/mountpoint`),
					{
						mountpoint: space.space_name,
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
