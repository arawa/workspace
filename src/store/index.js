import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

export default new Vuex.Store({
	state: {
		spaces: {},
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
		removeGroupFromSpace(state, name, group) {
			const space = state.spaces[name]
			delete space.groups[group]
			Vue.set(state.spaces, name, space)
		},
		setSpaceQuota(state, name, quota) {
			const space = state.spaces[name]
			space.quota = quota
			Vue.set(state.spaces, name, space)
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
