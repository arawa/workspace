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

import { User } from '@nextcloud/e2e-test-server/cypress'
import {
	addSpaceManager,
	createGeneralManager,
	createSpace,
	editSpace,
	GB,
	randomSuffix,
	serverPath,
	setLanguage,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The apps menu belongs to the server
const labels = {
	en: {
		openAppsMenu: 'Open apps menu',
		// Testing Library turns the non-breaking space of the label into a space
		searchWorkspaces: 'Search workspaces …', // eslint-disable-line @nextcloud/l10n-non-breaking-space
		columns: ['', 'Workspace name', 'Quota', 'Workspace Managers'],
		unlimited: 'unlimited',
	},
	fr: {
		openAppsMenu: 'Ouvrir le menu des applications',
		searchWorkspaces: 'Rechercher des workspaces…',
		columns: ['', 'Nom du workspace', 'Quota', 'Workspace Managers'],
		unlimited: 'illimité',
	},
}

// Opens the app from the apps menu of the header, from the Files app
const openWorkspaceApp = (label) => {
	cy.visit('/apps/files')
	cy.findByRole('button', { name: label.openAppsMenu }).click()
	cy.findByRole('menuitem', { name: 'Workspace' }).click()
	cy.location('pathname').should('equal', serverPath('/apps/workspace/'))
}

// A General Manager sees the workspaces of every spec: searching from the navigation keeps
// those of a test. Waited for, since the search leaves after a delay and would otherwise
// answer the next test, logged out
const searchWorkspaces = (label, search) => {
	cy.intercept({ method: 'GET', pathname: '**/apps/workspace/workspaces/count', query: { search } }).as('countSearch')
	cy.intercept({ method: 'GET', pathname: '**/apps/workspace/spaces', query: { search } }).as('spacesSearch')
	workspaceNavigation().findByLabelText(label.searchWorkspaces).type(search)
	cy.wait(['@countSearch', '@spacesSearch'])
}

// The rows of the table of all workspaces, and the row of one of them
const workspaceRows = () => cy.get('.table-spaces tbody tr')
const workspaceRow = (space) => cy.contains('.table-spaces tr', space.name)

// The cells of a row, in the order of the columns: color, name, quota, Workspace Managers
const QUOTA = 2
const MANAGERS = 3

// Checks the display names of the avatars of a row, in their order. Read inside the
// assertion, so it retries while the avatars are still loading
const shouldListManagers = (space, names) => workspaceRow(space)
	.find('td')
	.eq(MANAGERS)
	.find('.avatardiv')
	.should(($avatars) => {
		expect([...$avatars].map((avatar) => avatar.title)).to.deep.equal(names)
	})

// Shared by the names of the workspaces and the UIDs of the managers, so the tests
// find their own among those of the other specs
const tag = randomSuffix()
const manager = (name) => new User(`${name}-${tag}`, `${name}-${tag}`)

// The Workspace Managers of the first workspace. Their display names run the other way
// round from their UIDs, so the order of the avatars tells which one the list follows
const anna = manager('anna')
const zoe = manager('zoe')

// Shared by every test: each language only changes that of the users who log in
let generalManager
let first
let second

before(() => {
	cy.createUser(anna)
	cy.modifyUser(anna, 'displayname', 'Zélie')
	cy.createUser(zoe)
	cy.modifyUser(zoe, 'displayname', 'Ada')

	createGeneralManager('en').then((user) => {
		generalManager = user
		createSpace(generalManager, `${tag} first`).then((space) => {
			first = space
			editSpace(generalManager, first, { quota: GB })
			// Added out of order
			addSpaceManager(generalManager, first, zoe)
			addSpaceManager(generalManager, first, anna)
		})
		createSpace(generalManager, `${tag} second`).then((space) => {
			second = space
			addSpaceManager(generalManager, second, anna)
		})
	})
})

for (const [language, label] of Object.entries(labels)) {
	describe(`All workspaces (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
			setLanguage(anna, language)
		})

		// The table lists both workspaces with their quota, and the avatars of their
		// Workspace Managers sorted by UID
		const checkTable = () => {
			cy.get('.table-spaces th').then(($columns) => {
				expect([...$columns].map((column) => column.textContent.trim())).to.deep.equal(label.columns)
			})

			workspaceRow(first).find('td').eq(QUOTA).should('have.text', '1 GB')
			shouldListManagers(first, ['Zélie', 'Ada'])

			workspaceRow(second).find('td').eq(QUOTA).should('have.text', label.unlimited)
			shouldListManagers(second, ['Zélie'])
		}

		it('lists every workspace to a General Manager', () => {
			cy.login(generalManager)
			openWorkspaceApp(label)

			searchWorkspaces(label, tag)
			workspaceRows().should('have.length', 2)
			checkTable()
		})

		it('lists their workspaces to a Workspace Manager', () => {
			cy.login(anna)
			openWorkspaceApp(label)

			workspaceRows().should('have.length', 2)
			checkTable()
		})
	})
}
