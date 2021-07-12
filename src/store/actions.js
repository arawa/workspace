/*
 - @copyright 2021 Arawa <TODO>
 -
 - @author 2021 Cyrille Bollu <cyrille@bollu.be>
 -
 - @license <TODO>
*/

import router from '../router'
import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX } from '../constants'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
	// Adds a user to a group
	addUserToGroup(context, { name, gid, user }) {
		// Update frontend
		context.commit('addUserToGroup', { name, gid, user })

		// Update backend and revert frontend changes if something fails
		const space = context.state.spaces[name]
		const url = generateUrl('/apps/workspace/api/group/addUser/{spaceId}', { spaceId: space.id })
		axios.patch(url, {
			gid,
			user: user.uid,
		}).then((resp) => {
			if (resp.status === 204) {
				// eslint-disable-next-line no-console
				console.log('User ' + user.name + ' added to group ' + gid)
			} else {
				// Restore frontend and inform user
				context.commit('removeUserFromGroup', { name, gid, user })
				this._vm.$notify({
					title: t('workspace', 'Error'),
					text: t('workspace', 'An error occured while trying to add user ') + user.name
						+ t('workspace', ' to workspaces.') + '<br>'
						+ t('workspace', 'The error is: ') + resp.statusText,
					type: 'error',
				})
			}
		}).catch((e) => {
			// Restore frontend and inform user
			context.commit('removeUserFromGroup', { name, gid, user })
			this._vm.$notify({
				title: t('workspace', 'Network error'),
				text: t('workspace', 'A network error occured while trying to add user ') + user.name
					+ t('workspace', ' to workspaces.') + '<br>'
					+ t('workspace', 'The error is: ') + e,
				type: 'error',
			})
		})
	},
	// Creates a group and navigates to its details page
	createGroup(context, { name, group }) {
		// Groups must be postfixed with the ID of the space they belong
		const space = context.state.spaces[name]
		group = group + '-' + space.id

		// Creates group in frontend
		context.commit('addGroupToSpace', { name, group })

		// Creates group in backend
		axios.post(generateUrl(`/apps/workspace/api/group/${group}`), { spaceId: space.id })
			.then((resp) => {
				if (resp.status === 200) {
					// Navigates to the group's details page
					context.state.spaces[name].isOpen = true
					router.push({
						path: `/group/${name}/${group}`,
					})
					// eslint-disable-next-line no-console
					console.log('Group ' + group + ' created')
				} else {
					context.commit('removeGroupFromSpace', { name, group })
					this._vm.$notify({
						title: t('workspace', 'Error'),
						text: t('workspace', 'An error occured while trying to create group ') + group + t('workspace', '<br>The error is: ') + resp.statusText,
						type: 'error',
					})
				}
			})
			.catch((e) => {
				context.commit('removeGroupFromSpace', { name, group })
				this._vm.$notify({
					title: t('workspace', 'Network error'),
					text: t('workspace', 'A network error occured while trying to create group ') + group + t('workspace', '<br>The error is: ') + e,
					type: 'error',
				})
			})
	},
	// Deletes a group
	deleteGroup(context, { name, group }) {
		const space = context.state.spaces[name]

		// Deletes group from frontend
		context.commit('removeGroupFromSpace', { name, group })

		// Naviagte back to home
		router.push({
			path: '/',
		})

		// Deletes group from backend
		axios.delete(generateUrl(`/apps/workspace/api/group/${group}`), { data: { spaceId: space.id } })
			.then((resp) => {
				if (resp.status === 200) {
					// eslint-disable-next-line no-console
					console.log('Group ' + group + ' deleted')
				} else {
					context.commit('addGroupToSpace', { name, group })
					// TODO Inform user
				}
			})
			.catch((e) => {
				context.commit('addGroupToSpace', { name, group })
				// TODO Inform user
			})
	},
	removeSpace(context, { space }) {
		context.commit('deleteSpace', {
			space,
		})
	},
	// Removes a user from a group
	removeUserFromGroup(context, { name, gid, user }) {
		// Update frontend
		context.commit('removeUserFromGroup', { name, gid, user })

		// Update backend and revert frontend changes if something fails
		const space = context.state.spaces[name]
		const url = generateUrl('/apps/workspace/api/group/delUser/{spaceId}', { spaceId: space.id })
		axios.patch(url, {
			gid,
			user: user.uid,
		}).then((resp) => {
			if (resp.status === 204) {
				// eslint-disable-next-line no-console
				console.log('User ' + user.name + ' removed from group ' + gid)
			} else {
				// TODO: Inform user
				context.commit('addUserToGroup', { name, gid, user })
			}
		}).catch((e) => {
			// TODO: Inform user
			context.commit('addUserToGroup', { name, gid, user })
		})
	},
	// Removes a user from a space
	removeUserFromSpace(context, { name, user }) {
		const space = context.state.spaces[name]
		context.commit('removeUserFromWorkspace', { name, user })
		axios.delete(generateUrl('/apps/workspace/api/space/{spaceId}/user/{userId}', {
			spaceId: space.id,
			userId: user.uid,
		}))
			.then((resp) => {
				if (resp.status !== 200) {
					// Revert action an inform user
					context.commit('addUserToWorkspace', user)
					this._vm.$notify({
						title: t('workspace', 'Error'),
						text: t('workspace', 'An error occured while trying to add user ') + user.name + t('workspace', ' to workspace.<br>The error is: ') + resp.statusText,
						type: 'error',
					})
				}
			}).catch((e) => {
				// Revert action an inform user
				context.commit('addUserToWorkspace', user)
				this._vm.$notify({
					title: t('workspace', 'Network error'),
					text: t('workspace', 'A networks error occured while trying to add user ') + user.name + t('workspace', ' to workspace.<br>The error is: ') + e,
					type: 'error',
				})
			})
		// eslint-disable-next-line no-console
		console.log('User ' + user.name + ' removed from space ' + name)
	},
	// Renames a group and navigates to its details page
	renameGroup(context, { name, gid, newGroupName }) {
		const space = context.state.spaces[name]
		const oldGroupName = space.groups[gid].displayName

		// Groups must be postfixed with the ID of the space they belong
		newGroupName = newGroupName + '-' + space.id

		// Creates group in frontend
		context.commit('renameGroup', { name, gid, newGroupName })

		// Creates group in backend
		axios.patch(generateUrl(`/apps/workspace/api/group/${gid}`), { spaceId: space.id, newGroupName })
			.then((resp) => {
				if (resp.status === 200) {
					// Navigates to the group's details page
					context.state.spaces[name].isOpen = true
					// eslint-disable-next-line no-console
					console.log('Group ' + gid + ' renamed to ' + newGroupName)
				} else {
					context.commit('renameGroup', { name, gid, oldGroupName })
					// TODO Inform user
				}
			})
			.catch((e) => {
				context.commit('renameGroup', { name, gid, oldGroupName })
				// TODO Inform user
			})
	},
	// Change a user's role from admin to user (or the opposite way)
	toggleUserRole(context, { name, user }) {
		const space = context.state.spaces[name]
		if (user.role === 'admin') {
			user.role = 'user'
			user.groups.splice(user.groups.indexOf(ESPACE_MANAGERS_PREFIX + name), 1)
			user.groups.push(ESPACE_USERS_PREFIX + name)
		} else {
			user.role = 'admin'
			user.groups.splice(user.groups.indexOf(ESPACE_USERS_PREFIX + name), 1)
			user.groups.push(ESPACE_MANAGERS_PREFIX + name)
		}
		context.commit('updateUser', { name, user })
		axios.patch(generateUrl('/apps/workspace/api/space/{spaceId}/user/{userId}', {
			spaceId: space.id,
			userId: user.uid,
		}))
			.then((resp) => {
				if (resp.status === 200) {
					// eslint-disable-next-line no-console
					console.log('Role of user ' + user.name + ' changed')
				} else {
					// Revert action an inform user
					if (user.role === 'admin') {
						user.role = 'user'
						user.groups.splice(user.groups.indexOf(ESPACE_MANAGERS_PREFIX + name), 1)
						user.groups.push(ESPACE_USERS_PREFIX + name)
					} else {
						user.role = 'admin'
						user.groups.splice(user.groups.indexOf(ESPACE_USERS_PREFIX + name), 1)
						user.groups.push(ESPACE_MANAGERS_PREFIX + name)
					}
					context.commit('updateUser', { name, user })
					this._vm.$notify({
						title: t('workspace', 'Error'),
						text: t('workspace', 'An error occured while trying to change the role of user ') + user.name + t('workspace', '.<br>The error is: ') + resp.statusText,
						type: 'error',
					})
				}
			}).catch((e) => {
				// Revert action an inform user
				if (user.role === 'admin') {
					user.role = 'user'
				} else {
					user.role = 'admin'
				}
				context.commit('updateUser', { name, user })
				this._vm.$notify({
					title: t('workspace', 'Network error'),
					text: t('workspace', 'An error occured while trying to change the role of user ') + user.name + t('workspace', '.<br>The error is: ') + e,
					type: 'error',
				})
			})
	},
	updateSpace(context, { space }) {
		context.commit('updateSpace', space)
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
		quota = (quota === 'unlimited') ? -3 : quota

		// Updates backend
		const space = context.state.spaces[name]
		const url = generateUrl(`/apps/groupfolders/folders/${space.id}/quota`)
		axios.post(url, { quota })
			.then(resp => {
				if (resp.status !== 200) {
					// Reverts change made in the frontend in case of error
					context.commit('setSpaceQuota', { name, oldQuota })
					this._vm.$notify({
						title: t('workspace', 'Error'),
						text: t('workspace', 'An error occured while trying to update the workspace\'s quota.<br>The error is: ') + resp.statusText,
						type: 'error',
					})
				}
			})
			.catch((e) => {
				// Reverts change made in the frontend in case of error
				context.commit('setSpaceQuota', { name, oldQuota })
				this._vm.$notify({
					title: t('workspace', 'Network error'),
					text: t('workspace', 'A network error occured while trying to update the workspace\'s quota.<br>The error is: ') + e,
					type: 'error',
				})
			})
	},
	updateColor(context, { name, colorCode }) {
		context.commit('UPDATE_COLOR', { name, colorCode })
	},
}
