import Vue from 'vue'
import Vuex from 'vuex'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

export default new Vuex.Store({
	state: {
		spaces: {},
	},
	actions: {
		removeUserFromSpace(context, { spaceName, user }) {
			context.commit('removeUserFromSpace', spaceName, user.name)
			axios.delete(generateUrl('/apps/workspace/api/space/{spaceName}/user/{userName}', {
				spaceName,
				userName: user.name,
			}))
				.then((resp) => {
					// eslint-disable-next-line
					console.log('resp', resp)
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
		},
	},
	mutations: {
		addGroupToSpace(state, name, group) {
			const space = state.spaces[name]
			space.groups[group] = group
			Vue.set(state.spaces, name, space)
		},
		addSpace(state, space) {
			Vue.set(state.spaces, space.name, space)
		},
		addUserToAdminList(state, spaceName, user) {
			const space = state.spaces[spaceName]
			space.admins[user.name] = user
			Vue.set(state.spaces, spaceName, space)
		},
		addUserToUserList(state, spaceName, user) {
			const space = state.spaces[spaceName]
			space.users[user.name] = user
			Vue.set(state.spaces, spaceName, space)
		},
		removeGroupFromSpace(state, spaceName, group) {
			const space = state.spaces[spaceName]
			delete space.groups[group]
			Vue.set(state.spaces, spaceName, space)
		},
		removeUserFromSpace(state, spaceName, userName) {
			const space = state.spaces[spaceName]
			delete space.users[userName]
			delete space.admins[userName]
			Vue.set(state.spaces, spaceName, space)
		},
		setSpaceQuota(state, spaceName, quota) {
			const space = state.spaces[spaceName]
			space.quota = quota
			Vue.set(state.spaces, spaceName, space)
		},
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
