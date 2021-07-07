/**
 * @copyright 2021 Arawa
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license <TODO>
 *
 */

import Vue from 'vue'
import Notifications from 'vue-notification'
import router from './router'
import store from './store'
import App from './App.vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

Vue.use(Notifications)

Vue.mixin({
	methods: {
		t,
		n,
	},
})

export default new Vue({
	el: '#content',
	data: {
		isUserGeneralAdmin: false,
		spaces: {},
	},
	router,
	store,
	render: (h) => h(App),
})
