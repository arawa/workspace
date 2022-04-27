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
import axios from '@nextcloud/axios'
import { createSpace } from '../../services/spaceService'

jest.mock('axios')

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

	it('Convert 10000MB to 10GB', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('10737418240')
		expect(quota).toEqual('10GB')
	})

	it('Convert 23GB to 23GB', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('24696061952')
		expect(quota).toEqual('23GB')
	})

	it('Convert -3 (int) to unlimited', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend(-3)
		expect(quota).toEqual('unlimited')
	})

	it('Convert -3 (string) to unlimited', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('-3')
		expect(quota).toEqual('unlimited')
	})

	it('Return string type', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('10737418240')
		expect(typeof (quota)).toBe('string')
	})
})

describe('Creating spaces with different entries', () => {

	beforeEach(() => {
		axios.mockClear()
	})

	it('Home.methods.createSpace has been called', () => {
		const mockCreateSpace = jest.spyOn(Home.methods, 'createSpace')

		mockCreateSpace('44_Sri_Lanka')

		expect(mockCreateSpace).toHaveBeenCalled()
	})

	it('Create "Sri_Lanka"', async () => {
		axios.post.mockResolvedValue({
			data: {
				space_name: 'Sri_Lanka',
				id_space: 421,
				folder_id: 809,
				color: '#b7752e',
				groups: {},
				statuscode: 201,
				acl: {
					state: true,
				},
				space_advanced_permissions: true,
				assign_permission: {
					status: 'enabled',
					groups: [
						'SPACE-GE-421',
					],
				},
			},
		})

		const result = await createSpace('Sri_Lanka', 809)

		expect(result).not.toBe(undefined)
		expect(result.statuscode).toEqual(201)
	})
})
