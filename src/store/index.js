import Vue from 'vue'
import Vuex from 'vuex'
import actions from './actions'
import { getters } from './getters'
import mutations from './mutations'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

const store = new Vuex.Store({
	state: {
		loading: true,
		spaces: {},
	},
	mutations,
	actions,
	getters,
})

export default store
