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
