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

import { getUsers, addGroupToWorkspace } from '../services/spaceService.js'
import { generateUrl } from '@nextcloud/router'
import { PREFIX_GID_SUBGROUP_SPACE, PREFIX_DISPLAYNAME_SUBGROUP_SPACE } from '../constants.js'
import axios from '@nextcloud/axios'
import showNotificationError from '../services/Notifications/NotificationError.js'
import ManagerGroup from '../services/Groups/ManagerGroup.js'
import router from '../router.js'
import UserGroup from '../services/Groups/UserGroup.js'

export default {
	// Adds a user to a group
	// The backend takes care of adding the user to the U- group, and Workspace Managers group if needed.
	addUserToGroup(context, { name, gid, user }) {
		// Update frontend
		context.commit('addUserToGroup', { name, gid, user })

		// Update backend and revert frontend changes if something fails
		const space = context.state.spaces[name]
		const url = generateUrl('/apps/workspace/api/group/addUser/{spaceId}', { spaceId: space.id })
		axios.post(url, {
			gid,
			user: user.uid,
			workspace: space,
		}).then((resp) => {
			if (resp.status === 201) {
				// Everything went well, we can thus also add this user to the UGroup in the frontend
				context.commit('addUserToGroup', {
					name,
					gid: context.getters.UGroup(name),
					user,
				})
				// eslint-disable-next-line no-console
				console.log('User ' + user.name + ' added to group ' + gid)
			} else {
				// Restore frontend and inform user
				context.commit('removeUserFromGroup', { name, gid, user })
				const text = t('workspace', 'An error occured while trying to add user ') + user.name
				showNotificationError('Error', text, 5000)
			}
		}).catch((e) => {
			// Restore frontend and inform user
			context.commit('removeUserFromGroup', { name, gid, user })
			console.error('The error is : ' + e)
			console.error('e.message', e.message)
			console.error('e.name', e.name)
			console.error('e.lineNumber', e.lineNumber)
			console.error('e.columnNumber', e.columnNumber)
			console.error('e.stack', e.stack)
			const text = t('workspace', 'A network error occured while trying to add user {user_name} to workspaces.<br>The error is: {error}', { user_name: user.name, error: e })
			showNotificationError('Network error', text, 4000)
		})
	},
	incrementGroupUserCount(context, { spaceName, gid }) {
		context.commit('INCREMENT_GROUP_USER_COUNT', { spaceName, gid })
	},
	incrementSpaceUserCount(context, { spaceName }) {
		context.commit('INCREMENT_SPACE_USER_COUNT', { spaceName })
	},
	decrementGroupUserCount(context, { spaceName, gid }) {
		context.commit('DECREMENT_GROUP_USER_COUNT', { spaceName, gid })
	},
	decrementSpaceUserCount(context, { spaceName }) {
		context.commit('DECREMENT_SPACE_USER_COUNT', { spaceName })
	},
	substractionSpaceUserCount(context, { spaceName, usersCount }) {
		context.commit('SUBSTRACTION_SPACE_USER_COUNT', { spaceName, usersCount })
	},
	substractionGroupUserCount(context, { spaceName, gid, usersCount }) {
		context.commit('SUBSTRACTION_GROUP_USER_COUNT', { spaceName, gid, usersCount })
	},
	// Creates a group and navigates to its details page
	createGroup(context, { name, gid }) {
		// Groups must be postfixed with the ID of the space they belong
		const space = context.state.spaces[name]
		const spaceId = space.id
		const displayName = `${PREFIX_DISPLAYNAME_SUBGROUP_SPACE}${gid}-${space.name}`
		gid = `${PREFIX_GID_SUBGROUP_SPACE}${gid}-${space.id}`

		const groups = Object.keys(space.groups)
		if (groups.includes(gid)) {
			showNotificationError('Duplication of groups', 'The group already exists.', 3000)
			return
		}

		// Creates group in backend
		axios.post(generateUrl('/apps/workspace/api/group'),
			{
				data: {
					spaceId,
					gid,
					displayName,
				},
				spaceId: space.id
			})
			.then((resp) => {
				addGroupToWorkspace(space.id, resp.data.group.gid)
				// Creates group in frontend
				context.commit('addGroupToSpace', {
					name,
					gid,
					displayName,
          types: resp.data.group.types,
					slug: resp.data.group.slug
				})
				// Navigates to the g roup's details page
				context.state.spaces[name].isOpen = true
				router.push({
					path: `/group/${name}/${resp.data.group.slug}`,
				})
				// eslint-disable-next-line no-console
				console.log('Group ' + gid + ' created')
			})
			.catch((e) => {
				context.commit('removeGroupFromSpace', { name, gid })
				const message = (e.response && e.response.data && e.response.data.msg) ?? e.message
				const text = t('workspace', 'A network error occured while trying to create group {group}<br>The error is: {error}', { error: message, group: gid })
				showNotificationError('Network error', text, 4000)
			})
	},
	// Deletes a group
	deleteGroup(context, { name, gid }) {
		const space = context.state.spaces[name]

		// Deletes group from frontend
		context.commit('removeGroupFromSpace', { name, gid })

		// Naviagte back to home
		router.push({
			path: `/workspace/${name}`,
		})

		// Deletes group from backend
		axios.delete(generateUrl(`/apps/workspace/api/group/${gid}`), { data: { spaceId: space.id } })
			.then((resp) => {
				if (resp.status === 200) {
					// eslint-disable-next-line no-console
					console.log('Group ' + gid + ' deleted')
				} else {
					context.commit('addGroupToSpace', { name, gid })
					const text = t('workspace', 'An error occured while trying to delete group {group}<br>The error is: {error}', { group: gid, error: resp.statusText })
					showNotificationError('Error', text, 3000)
				}
			})
			.catch((e) => {
				context.commit('addGroupToSpace', { name, gid })
				const text = t('workspace', 'Network error occured while trying to delete group {group}<br>The error is: {error}', { group: gid, error: e })
				showNotificationError('Network error', text, 3000)
			})
	},
	// Deletes a space
	removeSpace(context, { space }) {
		context.commit('deleteSpace', {
			space,
		})
	},
	// Remove a user from a workspace
	removeUserFromWorkspace(context, { name, gid, user }) {
		const space = JSON.parse(JSON.stringify(context.state.spaces[name]))
		const backupGroups = space.users[user.uid].groups

		context.commit('removeUserFromWorkspace', { name, user })

		const url = generateUrl('/apps/workspace/spaces/{spaceId}/users/{user}/groups', { spaceId: space.id, user: user.uid })
		axios.patch(url, {
			space,
			gid,
			user: user.uid,
		}).then((resp) => {
			if (resp.data.statuscode === 204) {
				// eslint-disable-next-line no-console
				console.log('User ' + user.name + ' removed from group ' + gid)
			} else {
				const text = t('workspace', 'An error occured while removing user from group {group}<br>The error is: {error}', { group: gid, error: resp.statusText })
				showNotificationError('Error', text, 4000)
				context.commit('addUserToGroup', { name, gid, user })
			}
		}).catch((e) => {
			const text = t('workspace', 'Network error occured while removing user from group {group}<br>The error is: {error}', { group: gid, error: e })
			showNotificationError('Error', text, 4000)
			if (gid === UserGroup.getGid(space)) {
				backupGroups.forEach(group =>
					context.commit('addUserToGroup', { name, group, user }),
				)
			} else {
				context.commit('addUserToGroup', { name, gid, user })
			}
		})
	},
	// Removes a user from a group
	removeUserFromGroup(context, { name, gid, user }) {
		// It's a deep copy to copy the object and not the reference.
		// src: https://www.samanthaming.com/tidbits/70-3-ways-to-clone-objects/#shallow-clone-vs-deep-clone
		const space = JSON.parse(JSON.stringify(context.state.spaces[name]))
		const backupGroups = space.users[user.uid].groups
		// Update frontend
		if (gid === UserGroup.getGid(space)) {
			context.commit('removeUserFromWorkspace', { name, user })
		} else {
			context.commit('removeUserFromGroup', { name, gid, user })
		}
		// Update backend and revert frontend changes if something fails
		let ressource = '/apps/workspace/api/group/delUser/{spaceId}'
		if (gid.startsWith('SPACE-U')) {
			ressource += '?cascade=true'
		}
		const url = generateUrl(ressource, { spaceId: space.id })
		axios.patch(url, {
			space,
			gid,
			user: user.uid,
		}).then((resp) => {
			if (resp.data.statuscode === 204) {
				// eslint-disable-next-line no-console
				console.log('User ' + user.name + ' removed from group ' + gid)
			} else {
				const text = t('workspace', 'An error occured while removing user from group {group}<br>The error is: {error}', { group: gid, error: resp.statusText })
				showNotificationError('Error', text, 4000)
				context.commit('addUserToGroup', { name, gid, user })
			}
		}).catch((e) => {
			const text = t('workspace', 'Network error occured while removing user from group {group}<br>The error is: {error}', { group: gid, error: e })
			showNotificationError('Error', text, 4000)
			if (gid === UserGroup.getGid(space)) {
				backupGroups.forEach(group =>
					context.commit('addUserToGroup', { name, group, user }),
				)
			} else {
				context.commit('addUserToGroup', { name, gid, user })
			}
		})
	},
	// Renames a group and navigates to its details page
	renameGroup(context, { name, gid, newGroupName }) {
		const space = context.state.spaces[name]

		// Creates group in backend
		axios.patch(generateUrl(`/apps/workspace/api/group/${gid}`), { spaceId: space.id, newGroupName })
			.then((resp) => {
				if (resp.status === 200) {
					// Navigates to the group's details page
					context.state.spaces[name].isOpen = true
					// eslint-disable-next-line no-console
					console.log('Group ' + gid + ' renamed to ' + newGroupName)

					// Creates group in frontend
					context.commit('renameGroup', { name, gid, newGroupName })
				}
			})
			.catch((e) => {
				console.error(e)
				showNotificationError('Error', e.response.data, 4000)
			})
	},
	// Change a user's role from admin to user (or the opposite way)
	toggleUserRole(context, { name, user }) {
		const space = context.state.spaces[name]
		if (context.getters.isSpaceAdmin(user, name)) {
			user.groups.splice(user.groups.indexOf(ManagerGroup.getGid(space)), 1)
			context.commit('DECREMENT_GROUP_USER_COUNT', {
				spaceName: space.name,
				gid: ManagerGroup.getGid(space)
			})
			context.commit('CHANGE_USER_ROLE', {
				spaceName: space.name,
				user,
				role: 'user',
			})
		} else {
			user.groups.push(ManagerGroup.getGid(space))
			context.commit('INCREMENT_GROUP_USER_COUNT', {
				spaceName: space.name,
				gid: ManagerGroup.getGid(space)
			})
			context.commit('CHANGE_USER_ROLE', {
				spaceName: space.name,
				user,
				role: 'admin',
			})
		}
		const spaceId = space.id
		const userId = user.uid
		context.commit('updateUser', { name, user })
		axios.patch(generateUrl(`/apps/workspace/api/space/${spaceId}/user/${userId}`),
			{
				space,
				userId,
			})
			.then((resp) => {
				if (resp.status === 200) {
					// eslint-disable-next-line no-console
					console.log('Role of user ' + user.name + ' changed')
				} else {
					// Revert action an inform user
					if (context.getters.isSpaceAdmin(user, name)) {
						user.groups.splice(user.groups.indexOf(ManagerGroup.getGid(space)), 1)
					} else {
						user.groups.push(ManagerGroup.getGid(space))
					}
					context.commit('updateUser', { name, user })
					const text = t('workspace', 'An error occured while trying to change the role of user {user}.<br>The error is: {error}', { user: user.name, error: resp.statusText })
					showNotificationError('Error', text, 3000)
				}
			}).catch((e) => {
				// Revert action an inform user
				if (context.getters.isSpaceAdmin(user, name)) {
					user.groups.splice(user.groups.indexOf(ManagerGroup.getGid(space)), 1)
				} else {
					user.groups.push(ManagerGroup.getGid(space))
				}
				context.commit('updateUser', { name, user })
				const text = t('workspace', 'Network error occured while trying to change the role of user {user}.<br>The error is: {error}', { user: user.name, error: e })
				showNotificationError('Network error', text, 3000)
			})
	},
	updateSpace(context, { space }) {
		context.commit('updateSpace', space)
	},
	removeConnectedGroup(context, { spaceId, gid, name }) {
		axios.delete(generateUrl(`/apps/workspace/spaces/${spaceId}/connected-groups/${gid}`))
			.then((resp) => {
			})
			.catch((e) => {
				console.error('Error to remove connected group', e.message)
				console.error(e)
			})

		context.commit('removeAddedGroupFromSpace', { name, gid })

		// Naviagte back to home
		router.push({
			path: `/workspace/${name}`,
		})
	},
	addConnectedGroupToWorkspace(context, { spaceId, group, name }) {
		const space = context.state.spaces[name]
		const result = axios.post(generateUrl(`/apps/workspace/spaces/${spaceId}/connected-groups/${group.gid}`))
			.then(resp => {
				context.commit('addConnectedGroupToWorkspace', { name, group, slug: resp.data.slug })
				const users = resp.data.users
				for (const user in users) {
					context.commit('addUserToWorkspace', { name, user: users[user] })
					context.commit('addUserToGroup', { name, gid: group.gid, user: users[user] })
					context.commit('INCREMENT_ADDED_GROUP_USER_COUNT', { spaceName: name, gid: group.gid })
					context.commit('INCREMENT_GROUP_USER_COUNT', { spaceName: name, gid: UserGroup.getGid(space) })
					context.commit('INCREMENT_SPACE_USER_COUNT', { spaceName: name })
				}
				return resp.data
			})
			.catch(error => {
				console.error('Error to add connected group', error.message)
				console.error(error)
			})

		return result
	},
	setSpaceQuota(context, { name, quota }) {
		// Updates frontend
		const oldQuota = context.getters.quota(name)
		context.commit('setSpaceQuota', { name, quota })

		// Transforms quota for backend
		switch (quota.substr(-2).toLowerCase()) {
		case 'tb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 4
			break
		case 'gb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 3
			break
		case 'mb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 2
			break
		case 'kb':
			quota = quota.substr(0, quota.length - 2) * 1024
			break
		}
		quota = (quota === t('workspace', 'unlimited')) ? -3 : quota

		// Updates backend
		const space = context.state.spaces[name]
		const url = generateUrl(`/apps/groupfolders/folders/${space.groupfolderId}/quota`)
		axios.post(url, { quota })
			.then(resp => {
				if (resp.status !== 200) {
					// Reverts change made in the frontend in case of error
					context.commit('setSpaceQuota', { name, oldQuota })
					const text = t('workspace', 'An error occured while trying to update the workspace\'s quota.<br>The error is: {error}', { error: resp.statusText })
					showNotificationError('Error', text, 3000)
				}
			})
			.catch((e) => {
				// Reverts change made in the frontend in case of error
				context.commit('setSpaceQuota', { name, oldQuota })
				const text = t('workspace', 'Network error occured while trying to update the workspace\'s quota.<br>The error is: {error}', { error: e })
				showNotificationError('Network error', text, 3000)
			})
	},
	updateColor(context, { name, colorCode }) {
		context.commit('UPDATE_COLOR', { name, colorCode })
	},
	emptyGroupfolders(context) {
		context.commit('EMPTY_GROUPFOLDERS')
	},
	updateGroupfolders(context, { groupfolder }) {
		context.commit('UPDATE_GROUPFOLDERS', { groupfolder })
	},
	async addUsersFromCSV(context, { formData }) {
		const url = generateUrl('/apps/workspace/file/csv/import-data')
		const resp = await axios({
			method: 'POST',
			url,
			data: formData,
		})
		return resp.data
	},
	async importCsvFromFiles(context, { formData }) {
		const url = generateUrl('/apps/workspace/file/csv/import-from-files')
		const resp = await axios({
			method: 'POST',
			url,
			data: formData,
		})
		return resp.data
	},
	loadUsers(context, { space }) {
		context.commit('SET_LOADING_USERS_WAITTING', ({ activated: false }))
		context.commit('SET_NO_USERS', ({ activated: false }))

		if (Object.keys(space.users).length === space.userCount) {
			return
		}

		context.commit('SET_LOADING_USERS_WAITTING', ({ activated: true }))

		getUsers(space.id)
			.then(users => {
				if (Object.keys(users).length === 0) {
					context.commit('SET_LOADING_USERS_WAITTING', ({ activated: false }))
					context.commit('SET_NO_USERS', ({ activated: true }))
					return
				}

				if (Object.keys(users).length > 0) {
					context.commit('SET_LOADING_USERS_WAITTING', ({ activated: false }))
					context.commit('SET_NO_USERS', ({ activated: false }))
					context.commit('UPDATE_USERS', {
						space,
						users
					})
				}
			})
			.catch(error => {
				console.error('Impossible to get users for the workspace.')
				console.error(error)
			})
	},
}
