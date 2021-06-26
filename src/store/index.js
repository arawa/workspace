import Vue from 'vue'
import Vuex from 'vuex'
import actions from './actions'
import { getters } from './getters'
import mutations from './mutations'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

export default new Vuex.Store({
	state: {
		spaces: {},
	},
	mutations,
	actions,
	getters,
})
