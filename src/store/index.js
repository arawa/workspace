import Vue from 'vue'
import Vuex from 'vuex'
import mutations from './mutations'

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
	mutations,
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
