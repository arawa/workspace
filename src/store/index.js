/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import actions from './actions.js'
import { getters } from './getters.js'
import mutations from './mutations.js'

Vue.use(Vuex)
Vue.config.devtools = true // Debug mode

const store = new Store({
	state: {
		loading: true,
		noUsers: false,
		loadingUsersWaitting: false,
		spaces: {},
		groupfolders: {},
	},
	mutations,
	actions,
	getters,
})

export default store
