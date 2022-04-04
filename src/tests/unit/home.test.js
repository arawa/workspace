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

import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { createLocalVue, mount } from '@vue/test-utils'
import Home from '../../Home.vue'
import Vue from 'vue'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import store from '../../store/index.js'

Vue.prototype.t = t
Vue.prototype.n = n

const localVue = createLocalVue()
const router = new VueRouter()
localVue.use(Vuex)
localVue.use(VueRouter)
const wrappedHome = mount(Home, {
	store,
	localVue,
	router,
})

// const expect = require('chai').expect

describe('Home component tests', () => {

	it('ConvertQuotaForFrontend: Test regular quota', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('3221225472')
		expect(quota).toEqual('3GB')
	})

	it('ConvertQuotaForFrontend: Test unlimited quota', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend(-3)
		expect(quota).toEqual('unlimited')
	})
})
