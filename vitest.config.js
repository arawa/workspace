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

import vue from '@vitejs/plugin-vue'
import { join } from 'node:path'
import { defineConfig } from 'vitest/config'

const mock = (file) => join(import.meta.dirname, 'src', 'tests', '__mocks__', file)

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: [
			{ find: /^@nextcloud\/axios$/, replacement: mock('axios.js') },
			{ find: /^@nextcloud\/dialogs$/, replacement: mock('nextcloudDialogsMock.js') },
			{ find: /^@nextcloud\/files\/dav$/, replacement: mock('nextcloudFilesMock.js') },
			{ find: /^@nextcloud\/vue\/components\/.*$/, replacement: mock('nextcloudVueComponentMock.js') },
			{ find: /^@nextcloud\/vue\/composables\/useIsDarkTheme$/, replacement: mock('useIsDarkThemeMock.js') },
		],
	},
	test: {
		environment: 'jsdom',
		include: ['src/tests/unit/**/*.test.js'],
		setupFiles: ['src/tests/vitest.setup.js'],
		server: {
			deps: {
				// The Nextcloud packages ship untranspiled CSS imports.
				inline: [/@nextcloud\//],
			},
		},
	},
})
