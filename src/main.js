/**
 * @copyright 2021 Arawa
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license <TODO>
 *
 */

import Vue from 'vue'
import App from './App.vue'

/**
 * TODO: Fix this mixin issue to get translation utilities 
 */
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
Vue.mixin({
	methods: {
		t,
		n,
	},
})

export default new Vue({
	el: '#content',
	data: {
		spaces: {},
	},
	render: (h) => h(App),
})
