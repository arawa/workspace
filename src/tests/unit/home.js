import { createLocalVue, shallowMount } from '@vue/test-utils'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Home from '../../../src/Home.vue'
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
const wrappedHome = shallowMount(Home, {
	store,
	localVue,
	router,
})

const expect = require('chai').expect

describe('Home component tests', () => {

	it('ConvertQuotaForFrontend: Test regular quota', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('3221225472')
		expect(quota).equals('3GB')
	})

	it('ConvertQuotaForFrontend: Test unlimited quota', () => {
		const quota = wrappedHome.vm.convertQuotaForFrontend('-3')
		expect(quota).equals('unlimited')
	})
})
