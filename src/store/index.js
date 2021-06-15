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
		toggleUserRole(context, { name, user }) {
			if (user.role === 'admin') {
				user.role = 'user'
			} else {
				user.role = 'admin'
			}
			context.commit('updateUser', { name, user })
			axios.patch(generateUrl('/apps/workspace/api/space/{name}/user/{userId}', {
				name,
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
						context.commit('updateUser', { name, user })
					}
				}).catch((e) => {
					// Revert action an inform user
					// TODO Inform user
					if (user.role === 'admin') {
						user.role = 'user'
					} else {
						user.role = 'admin'
					}
					context.commit('updateUser', { name, user })
				})
			// eslint-disable-next-line no-console
			console.log('Role of user ' + user.name + ' changed')
		},
		updateSpace(context, { space }) {
			context.commit('addSpace', space)
		},
	},
	getters,
})
