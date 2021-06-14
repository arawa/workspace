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
		removeUserFromSpace(context, { spaceName, user }) {
			context.commit('removeUserFromAdminList', { spaceName, user })
			context.commit('removeUserFromUserList', { spaceName, user })
			axios.delete(generateUrl('/index.php/apps/workspace/api/space/{spaceName}/user/{userId}', {
				spaceName,
				userId: user.uid,
			}))
				.then((resp) => {
					if (resp.status !== 200) {
						// Revert action an inform user
						// TODO Inform user
						if (user.role === 'admin') {
							context.commit('addUserToAdminList', user)
						} else {
							context.commit('addUserToUserList', user)
						}
					}
				}).catch((e) => {
					// Revert action an inform user
					// TODO Inform user
					if (user.role === 'admin') {
						context.commit('addUserToAdminList', user)
					} else {
						context.commit('addUserToUserList', user)
					}
				})
			// eslint-disable-next-line no-console
			console.log('User ' + user.name + ' removed from space ' + spaceName)
		},
		toggleUserRole(context, { spaceName, user }) {
			if (user.role === 'admin') {
				user.role = 'user'
				context.commit('addUserToUserList', { spaceName, user })
				context.commit('removeUserFromAdminList', { spaceName, user })
			} else {
				user.role = 'admin'
				context.commit('addUserToAdminList', { spaceName, user })
				context.commit('removeUserFromUserList', { spaceName, user })
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
							context.commit('addUserToUserList', { spaceName, user })
							context.commit('removeUserFromAdminList', { spaceName, user })
						} else {
							user.role = 'admin'
							context.commit('addUserToAdminList', { spaceName, user })
							context.commit('removeUserFromUserList', { spaceName, user })
						}
					}
				}).catch((e) => {
					// Revert action an inform user
					// TODO Inform user
					if (user.role === 'admin') {
						user.role = 'user'
						context.commit('addUserToUserList', { spaceName, user })
						context.commit('removeUserFromAdminList', { spaceName, user })
					} else {
						user.role = 'admin'
						context.commit('addUserToAdminList', { spaceName, user })
						context.commit('removeUserFromUserList', { spaceName, user })
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
				space
			})
		}
	},
	getters: {
		sortedSpaces(state) {
			const sortedSpaces = {}
			Object.keys(state.spaces).sort().forEach((value, index) => {
				sortedSpaces[value] = state.spaces[value]
			})
			return sortedSpaces
		},
	},
})
