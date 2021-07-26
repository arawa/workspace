import { createLocalVue, shallowMount } from '@vue/test-utils'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import SelectUsers from '../../../src/SelectUsers.vue'
import Vue from 'vue'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import store from '../../../src/store/index.js'

Vue.prototype.t = t
Vue.prototype.n = n

const localVue = createLocalVue()
const router = new VueRouter()
localVue.use(Vuex)
localVue.use(VueRouter)
const wrappedSelectUsers = shallowMount(SelectUsers, {
	store,
	localVue,
	router,
})

const expect = require('chai').expect

describe('SelectUsers component tests', () => {

	wrappedSelectUsers.vm.$store.commit('addSpace', {
		color: '#3794ac',
		groups: {
			'GE-foobar': {
				gid: 'GE-foobar',
				displayName: 'GE-foobar',
			},
			'U-foobar': {
				gid: 'U-foobar',
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
		wrappedSelectUsers.vm.$route.params.group = 'subgroup-42'

		wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
		const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'subgroup-42')
		expect(count).equals(1)
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

		wrappedSelectUsers.vm.$route.params.group = undefined

		wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
		const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'U-foobar')
		expect(count).equals(1)
	})

	it('addUsersToWorkspaceOrGroup test: Adding a user to a workspace with admin role', () => {
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

		wrappedSelectUsers.vm.$route.params.group = undefined

		wrappedSelectUsers.vm.addUsersToWorkspaceOrGroup()
		const count = wrappedSelectUsers.vm.$store.getters.groupUserCount('foobar', 'GE-foobar')
		expect(count).equals(1)
	})
})
