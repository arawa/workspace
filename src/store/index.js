import Vue from 'vue'
import Vuex from 'vuex'
import { getters } from './getters'
import mutations from './mutations'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

export default new Vuex.Store({
	state: {
		spaces: {},
	},
	mutations,
	actions: {
		removeSpace(context, { space }) {
			context.commit('deleteSpace', {
				space,
			})
		},
		removeUserFromSpace(context, { name, user }) {
			context.commit('removeUserFromWorkspace', { name, user })
			axios.delete(generateUrl('/apps/workspace/api/space/{name}/user/{userId}', {
				name,
				userId: user.uid,
			}))
				.then((resp) => {
					if (resp.status !== 200) {
						// Revert action an inform user
						context.commit('addUserToWorkspace', user)
						this._vm.$notify({
							title: 'Error',
							text: 'An error occured while trying to add user ' + user.name + ' to workspace.<br>The error is: ' + resp.statusText,
							type: 'error',
						})
					}
				}).catch((e) => {
					// Revert action an inform user
					context.commit('addUserToWorkspace', user)
					this._vm.$notify({
						title: 'Network error',
						text: 'A network error occured while trying to add user ' + user.name + ' to workspace.<br>The error is: ' + e,
						type: 'error',
					})
				})
		},
		// Change a user's role from admin to user (or the opposite way)
		toggleUserRole(context, { name, user }) {
			if (user.role === 'admin') {
				user.role = 'user'
				// TODO use global constant
				user.groups.splice(user.groups.indexOf('GE-' + name), 1)
				user.groups.push('U-' + name)
			} else {
				user.role = 'admin'
				user.groups.splice(user.groups.indexOf('U-' + name), 1)
				user.groups.push('GE-' + name)
			}
			context.commit('updateUser', { name, user })
			axios.patch(generateUrl('/apps/workspace/api/space/{name}/user/{userId}', {
				name,
				userId: user.uid,
			}))
				.then((resp) => {
					if (resp.status !== 200) {
						// Revert action an inform user
						if (user.role === 'admin') {
							user.role = 'user'
							user.groups.splice(user.groups.indexOf('GE-' + name), 1)
							user.groups.push('U-' + name)
						} else {
							user.role = 'admin'
							user.groups.splice(user.groups.indexOf('U-' + name), 1)
							user.groups.push('GE-' + name)
						}
						context.commit('updateUser', { name, user })
						this._vm.$notify({
							title: 'Error',
							text: 'An error occured while trying to change the role of user ' + user.name + '.<br>The error is: ' + resp.statusText,
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
						title: 'Network error',
						text: 'An error occured while trying to change the role of user ' + user.name + '.<br>The error is: ' + e,
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
			const url = generateUrl(`/apps/groupfolders/folders/${name}/quota`)
			axios.post(url, { quota })
				.then(resp => {
					if (resp.status !== 200) {
						// Reverts change made in the frontend in case of error
						context.commit('setSpaceQuota', { name, oldQuota })
						this._vm.$notify({
							title: 'Error',
							text: 'An error occured while trying to update the workspace\'s quota.<br>The error is: ' + resp.statusText,
							type: 'error',
						})
					}
				})
				.catch((e) => {
					// Reverts change made in the frontend in case of error
					context.commit('setSpaceQuota', { name, oldQuota })
					this._vm.$notify({
						title: 'Network rrror',
						text: 'A network error occured while trying to update the workspace\'s quota.<br>The error is: ' + e,
						type: 'error',
					})
				})
		},
	},
	getters,
})
