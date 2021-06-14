import OC from './OC'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Home from '../../../src/Home.vue'
import Vue from 'vue'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import store from '../../../src/store/index.js'

global.OC = OC

Vue.prototype.t = t
Vue.prototype.n = n

const localVue = createLocalVue()
const router = new VueRouter()
localVue.use(Vuex)
localVue.use(VueRouter)
const wrappedHome = shallowMount(Home, {
	store,
	localVue,
	router,
})

const expect = require('chai').expect

describe('Home component tests', () => {

	// Space with users
	const space = {
		color: '#f5f5f5',
		groups: [],
		id: '42',
		isOpen: false,
		name: 'test-space',
		quota: 'unlimited',
		admins: [],
		users: {
			baptiste: {
				groups: ['test', 'GE-test-space', 'U-test-space'],
			},
			dorianne: {
				groups: ['GE-test-space', 'U-test-space'],
			},
			philippe: {
				groups: ['test', 'GE-test-space', 'U-test-space'],
			},
		},
	}

	// Space without users
	const emptySpace = {
		color: '#f5f5f5',
		groups: [],
		id: '42',
		isOpen: false,
		name: 'test-space',
		quota: 'unlimited',
		admins: [],
		users: [],
	}

	it('Count users in space', () => {
		const count = wrappedHome.vm.userCount(space)
		expect(count).equals(3)
	})

	it('Count users in empty space', () => {
		const count = wrappedHome.vm.userCount(emptySpace)
		expect(count).equals(0)
	})

	it('Count users in group', () => {
		const count = wrappedHome.vm.groupUserCount(space, 'test')
		expect(count).equals(2)
	})

})
