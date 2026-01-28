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

// import Vue from 'vue'
import { createApp } from 'vue'
import router from './router.js'
// import { linkTo } from '@nextcloud/router'
import store from './store/index.js'
import App from './App.vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import VueLazyComponent from '@xunlei/vue-lazy-component'
import { vElementVisibility } from '@vueuse/components'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('workspace', '', 'js/')

// Vue.directive('elementVisibility', vElementVisibility)
// Vue.use(VueLazyComponent)

const app = createApp(App)

app
	.use(VueLazyComponent)
	.use(vElementVisibility)
	.mixin({
		methods: {
			t,
			n,
		},
	})
	.use(store)
	.use(router)
	.mount('#content')

export default app
