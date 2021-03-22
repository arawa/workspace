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
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
Vue.mixin({
	t,
	n,
})
*/

export default new Vue({
	el: '#content',
	render: (h) => h(App),
})
