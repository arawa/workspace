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

import { createLocalVue, mount } from '@vue/test-utils'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import SelectUsers from '../../SelectUsers.vue'
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
const wrappedSelectUsers = mount(SelectUsers, {
	store,
	localVue,
	router,
})

// const expect = require('chai').expect

describe('SelectUsers component tests', () => {

	wrappedSelectUsers.vm.$store.commit('addSpace', {
		color: '#3794ac',
		groups: {
			'SPACE-GE-42': {
				gid: 'SPACE-GE-42',
				displayName: 'GE-foobar',
			},
			'SPACE-U-42': {
				gid: 'SPACE-U-42',
				displayName: 'U-foobar',
			},
			'subgroup-42': {
				gid: 'subgroup-42',
				displayName: 'subgroup-42',
			},
		},
		id: 42,
		groupfolderId: 42,
		isOpen: false,
		name: 'foobar',
		quota: '-3',
		users: {},
	})

	it('addUsersToWorkspaceOrGroup test: Adding a user to a subgroup', () => {
		wrappedSelectUsers.vm.allSelectedUsers = [
			{
				uid: 'admin',
				name: 'admin',
				email: 'admin@acme.org',
				subtitle: 'admin@acme.org',
				groups: [],
				role: 'admin',
			},
		]

		wrappedSelectUsers.vm.$route.params.space = 'foobar'
		wrappedSelectUsers.vm.$route.params.slug = 'subgroup-42'

		wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
		const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'subgroup-42')
		expect(count).toEqual(1)
	})

	it('addUsersToWorkspaceOrGroup test: Adding a user to a workspace', () => {
		wrappedSelectUsers.vm.allSelectedUsers = [
			{
				uid: 'john',
				name: 'John Doe',
				email: 'john@acme.org',
				subtitle: 'john@acme.org',
				groups: [],
				role: 'user',
			},
		]

		wrappedSelectUsers.vm.$route.params.slug = undefined

		wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
		const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'SPACE-U-42')
		expect(count).toEqual(1)
	})

	it(
		'addUsersToWorkspaceOrGroup test: Adding a user to a workspace with admin role',
		() => {
			wrappedSelectUsers.vm.allSelectedUsers = [
				{
					uid: 'admin',
					name: 'admin',
					email: 'admin@acme.org',
					subtitle: 'admin@acme.org',
					groups: [],
					role: 'admin',
				},
			]

			wrappedSelectUsers.vm.$route.params.slug = undefined

			wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
			const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'SPACE-GE-42')
			expect(count).toEqual(1)
		},
	)
})
