import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
	state: {
		spaces: {},
	},
	actions: {
		updateSpace(context, { space }) {
			context.commit('addSpace', space)
		},
		removeSpace(context, { space }) {
			context.commit('deleteSpace', {
				space
			})
		}
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
		deleteSpace(state, { space }) {
			delete state.spaces[space.name]
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
