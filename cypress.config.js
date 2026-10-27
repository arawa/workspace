/**
 * @copyright Copyright (c) 2026 Arawa
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
 */

import {
	configureNextcloud,
	runExec,
	runOcc,
	startNextcloud,
	stopNextcloud,
	waitOnNextcloud,
} from '@nextcloud/e2e-test-server/docker'
import { defineConfig } from 'cypress'

export default defineConfig({
	// 16/9 screen ratio
	viewportWidth: 1280,
	viewportHeight: 720,

	defaultCommandTimeout: 20000,

	// Tries again 2 more times on failure, but not in `cypress open`
	retries: {
		runMode: 2,
		openMode: 0,
	},

	// Needed to trigger `after:run` events with cypress open
	experimentalInteractiveRunEvents: true,

	// Prevent elements to be scrolled under the top bar during actions
	scrollBehavior: 'center',

	e2e: {
		testIsolation: false,

		async setupNodeEvents(on, config) {
			// Remove the container after the run, except on CI where the runner is thrown away anyway
			on('after:run', async () => {
				if (!process.env.CI) {
					await stopNextcloud()
				}
			})

			// Backs cy.runOccCommand() and cy.runCommand()
			on('task', {
				runExec: ({ command, options }) => runExec(command, options),
			})

			// Starts a fresh Nextcloud container with this app mounted read-only, built js/ included.
			// forceRecreate: never reuse a container left half-configured by an interrupted run.
			const ip = await startNextcloud(process.env.BRANCH || 'master', true, { exposePort: 8080, forceRecreate: true })
			config.baseUrl = `http://${ip}/index.php`
			await waitOnNextcloud(ip)

			// groupfolders first: the initial migration of Workspace needs its tables.
			// Enabling Workspace also creates the GeneralManager group.
			await configureNextcloud(['groupfolders', 'workspace'])

			// configureNextcloud() forces English for every user. Without it, users still default to
			// English, but a General Manager set to French sees the app in French
			await runOcc(['config:system:delete', 'force_language'])

			// Display names of the workspace groups start with prefixes an admin can change
			const appValue = async (key, defaultValue) => {
				const { stdout } = await runOcc(['config:app:get', 'workspace', key, '--default-value', defaultValue])
				return stdout.trim()
			}
			config.expose = {
				...config.expose,
				displayPrefixManagerGroup: await appValue('DISPLAY_PREFIX_MANAGER_GROUP', 'WM-'),
				displayPrefixUserGroup: await appValue('DISPLAY_PREFIX_USER_GROUP', 'U-'),
			}

			// A Team folder that exists before any workspace: on a fresh server the first workspace
			// and its Team folder would both get id 1, which hides any mix-up between the two ids
			await runOcc(['groupfolders:create', 'Existing Team folder'])

			return config
		},
	},
})
