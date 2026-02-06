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
import { vElementVisibility } from '@vueuse/components'
import { generateFilePath } from '@nextcloud/router'
import VueLazyload from 'vue-lazyload'

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('workspace', '', 'js/')

const app = createApp(App)

app
	.use(vElementVisibility)
	.mixin({
		methods: {
			t,
			n,
		},
	})
	.use(store)
	.use(router)
	.use(VueLazyload, {
		preLoad: 1.3,
		lazyComponent: true,
	})
	.mount('#content')

export default app
