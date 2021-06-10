import Vue from 'vue'
import Vuex from 'vuex'
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
						// TODO Inform user
						context.commit('addUserToWorkspace', user)
					}
				}).catch((e) => {
					// Revert action an inform user
					// TODO Inform user
					context.commit('addUserToWorkspace', user)
				})
			// eslint-disable-next-line no-console
			console.log('User ' + user.name + ' removed from space ' + name)
		},
		toggleUserRole(context, { spaceName, user }) {
			if (user.role === 'admin') {
				user.role = 'user'
			} else {
				user.role = 'admin'
			}
			axios.patch(generateUrl('/index.php/apps/workspace/api/space/{spaceName}/user/{userId}', {
				spaceName,
				userId: user.uid,
			}))
				.then((resp) => {
					if (resp.status !== 200) {
						// Revert action an inform user
						// TODO Inform user
						if (user.role === 'admin') {
							user.role = 'user'
						} else {
							user.role = 'admin'
						}
					}
				}).catch((e) => {
					// Revert action an inform user
					// TODO Inform user
					if (user.role === 'admin') {
						user.role = 'user'
					} else {
						user.role = 'admin'
					}
				})
			// eslint-disable-next-line no-console
			console.log('Role of user ' + user.name + ' changed')
		},
		updateSpace(context, { space }) {
			context.commit('addSpace', space)
		},
		removeSpace(context, { space }) {
			context.commit('deleteSpace', {
				space,
			})
		},
	},
	getters: {
		// Returns the number of users in a group
		groupUserCount: state => (spaceName, groupName) => {
			const users = state.spaces[spaceName].users
			if (users.length === 0) {
				return 0
			} else {
				// We count all users in the space who have 'groupName' listed in their 'groups' property
				return Object.values(users).filter(user => user.groups.includes(groupName)).length
			}
		},
		// Returns the number of users in a space
		spaceUserCount: state => name => {
			const users = state.spaces[name].users
			if (users.length === 0) {
				return 0
			} else {
				return Object.keys(users).length
			}
		},
		sortedSpaces: state => {
			const sortedSpaces = {}
			Object.keys(state.spaces).sort().forEach((value, index) => {
				sortedSpaces[value] = state.spaces[value]
			})
			return sortedSpaces
		},
	},
})
