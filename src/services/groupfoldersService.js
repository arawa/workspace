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
import { generateUrl } from '@nextcloud/router'
import { createSpace, isSpaceManagers, isSpaceUsers } from './spaceService'

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

// param: string
// return: true if such a groupfolder exists, false otherwise.
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

// Param : int folderId from a groupfolder
// return object
function enableAcl(folderId) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/acl`),
		{
			acl: 1
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

// Param int folderId
// Param string gid
// return object
export function addGroup(folderId, gid) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/groups`),
		{
			group: gid
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			console.error(`Impossible to attach the ${gid} group to groupfolder. May be a problem with the connection ?`, error)
		})
	return result
}

// Param int folderId
// Param string gid
// manageAcl boolean (default: true)
function manageACL(folderId, gid, manageAcl = true) {
	const result = axios.post(generateUrl(`/apps/groupfolders/folders/${folderId}/manageACL`),
		{
			mappingType: 'group',
			mappingId: gid,
			manageAcl
		})
		.then(resp => {
			return resp.data.ocs.data
		})
		.catch(error => {
			console.error('Impossible to add the Space Manager group in Manage ACL groupfolder', error)
		})
	return result
}

// Param string spaceName
// Return object data
export async function create(spaceName) {
	const data = { }
	data.data = { }
	const groupfolderIsExist = await checkGroupfolderNameExist(spaceName)
	// Todo: Should I return a boolean value ?
	if (groupfolderIsExist) {
		console.error(`The groupfolder with this name : "${spaceName}" already exist`)
		data.data.statuscode = 400
		return data
	}

	// Create groupfolder
	const groupfolderId = await axios.post(generateUrl('/apps/groupfolders/folders'),
		{
			mountpoint: spaceName
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
			spaceManagerGID
		]
	}

	return data
}

// Param object/json workspace
export function destroy(workspace) {
	// It's a post because it's not possible to send data with the DELETE verb.
	const result = axios.post(generateUrl('/apps/workspace/api/delete/spaces'),
		{
			workspace,
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

export function rename(workspace, newSpaceName) {
	// Response format to return
	const respFormat = {
		data: {}
	}
	respFormat.data.statuscode = 500
	respFormat.data.message = 'Rename the space is impossible.'

	if (!checkGroupfolderNameExist(workspace.name)) {
		respFormat.data.statuscode = 409
		respFormat.data.message = 'The space name already exist. We cannot rename with this name.'
		console.error('The groupfolder name already exist. Please, choose another name to rename your space.')
		return respFormat
	}
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
						mountpoint: space.space_name
					})
					.then(resp => {
						return resp
					})
					.catch(error => {
						console.error('Error to call Groupfolder\'s API', error)
					})
				return groupfolderUpdated
			}
		})
		.catch(error => {
			console.error('Problem to rename the space', error)
		})
	const respFormatFinal = workspaceUpdated
		.then(resultat => {
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
