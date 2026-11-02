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
	openWorkspaceApp,
	randomSuffix,
	searchWorkspaces,
	setLanguage,
	shouldHaveColumns,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in
const labels = {
	en: {
		columns: ['', 'Workspace name', 'Quota', 'Workspace Managers'],
		unlimited: 'unlimited',
	},
	fr: {
		columns: ['', 'Nom du workspace', 'Quota', 'Workspace Managers'],
		unlimited: 'illimité',
	},
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
			shouldHaveColumns('.table-spaces', label.columns)

			workspaceRow(first).find('td').eq(QUOTA).should('have.text', '1 GB')
			shouldListManagers(first, ['Zélie', 'Ada'])

			workspaceRow(second).find('td').eq(QUOTA).should('have.text', label.unlimited)
			shouldListManagers(second, ['Zélie'])
		}

		it('lists every workspace to a General Manager', () => {
			cy.login(generalManager)
			openWorkspaceApp(language)

			searchWorkspaces(language, tag)
			workspaceRows().should('have.length', 2)
			checkTable()
		})

		it('lists their workspaces to a Workspace Manager', () => {
			cy.login(anna)
			openWorkspaceApp(language)

			workspaceRows().should('have.length', 2)
			checkTable()
		})
	})
}
