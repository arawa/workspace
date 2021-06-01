import Vue from 'vue'
import Vuex from 'vuex'
import mutations from './mutations'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

export default new Vuex.Store({
	state: {
		spaces: {},
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
