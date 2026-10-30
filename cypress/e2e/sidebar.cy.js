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
	addSpaceMember,
	createGeneralManager,
	createSpace,
	createSubgroup,
	navigationBullet,
	navigationEntry,
	openWorkspaceApp,
	randomSuffix,
	searchWorkspaces,
	serverPath,
	setLanguage,
	spaceGroups,
	SUBGROUP,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager reads in the navigation, in each language the tests run in.
// The toggle of the navigation and the arrow of an entry belong to @nextcloud/vue
const labels = {
	en: {
		newWorkspace: 'New workspace',
		allWorkspaces: 'All workspaces',
		closeNavigation: 'Close navigation',
		openNavigation: 'Open navigation',
		openMenu: 'Open menu',
		collapseMenu: 'Collapse menu',
		workspaceGroups: 'Workspace groups',
	},
	fr: {
		newWorkspace: 'Nouveau workspace',
		allWorkspaces: 'Tous les workspaces',
		closeNavigation: 'Fermer la navigation',
		openNavigation: 'Ouvrir la navigation',
		openMenu: 'Ouvrir le menu',
		collapseMenu: 'Réduire le menu',
		workspaceGroups: 'Groupes workspace',
	},
}

// The color of the bullet, as the browser computes it from the hex color of the workspace
const rgb = (hex) => `rgb(${hex.match(/[0-9a-f]{2}/gi).map((byte) => parseInt(byte, 16)).join(', ')})`

// Shared by the names of the workspaces, so the search keeps those of this spec
const tag = randomSuffix()
const spaceName = (name) => `${tag} ${name}`

// The number of members of each workspace, in the order the workspaces are created:
// neither alphabetical nor case-sensitive order
const memberCounts = { bravo: 2, Charlie: 1, alpha: 0 }
// The order of the navigation, regardless of case
const sortedNames = ['alpha', 'bravo', 'Charlie']

// Shared by every test: each language only changes that of the General Manager
let generalManager
// The workspaces, by name
const spaces = {}

before(() => {
	createGeneralManager('en').then((user) => {
		generalManager = user
		for (const name of Object.keys(memberCounts)) {
			createSpace(generalManager, spaceName(name)).then((space) => {
				spaces[name] = space
			})
		}
	})
	// Queued after the workspaces, so they exist when these commands run. The members
	// never log in, so their language does not matter
	for (const [name, count] of Object.entries(memberCounts)) {
		for (let i = 0; i < count; i++) {
			cy.createRandomUser().then((member) => addSpaceMember(generalManager, spaces[name], member))
		}
	}
	cy.then(() => createSubgroup(generalManager, spaces.alpha, SUBGROUP))
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Navigation (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
		})

		it('lists the workspaces of a General Manager and opens or closes', () => {
			cy.login(generalManager)

			// 1. The app opens from the apps menu of the header, with its navigation
			openWorkspaceApp(language)
			workspaceNavigation().findByRole('button', { name: label.newWorkspace }).should('be.visible')

			// 2. "All workspaces" opens the table of the workspaces. From the hub of a workspace,
			// since the app already opened on that table
			cy.visit(`/apps/workspace/hub/${spaces.alpha.id}`)
			cy.get('.hub-home').should('be.visible')
			workspaceNavigation().findByRole('link', { name: label.allWorkspaces }).click()
			cy.location('pathname').should('equal', serverPath('/apps/workspace/'))
			cy.get('.table-spaces').should('be.visible')

			// 3. The toggle closes the navigation, which leaves the accessibility tree
			cy.findByRole('button', { name: label.closeNavigation }).click()
			workspaceNavigation().should('not.exist')
			cy.findByRole('button', { name: label.openNavigation }).should('have.attr', 'aria-expanded', 'false')

			// 4. Then opens it again
			cy.findByRole('button', { name: label.openNavigation }).click()
			workspaceNavigation().should('be.visible')
			cy.findByRole('button', { name: label.closeNavigation }).should('be.visible')

			// 5. Each workspace shows its color, its name, its number of members and an arrow
			searchWorkspaces(language, tag)
			for (const [name, count] of Object.entries(memberCounts)) {
				const space = spaces[name]
				navigationBullet(space.name).should('have.css', 'background-color', rgb(space.color))
				navigationEntry(space.name).within(() => {
					cy.get('.user-counter').should('have.text', String(count))
					cy.findByRole('button', { name: label.openMenu }).should('be.visible')
				})
			}

			// 6. The workspaces are sorted alphabetically, regardless of case. Each link of the
			// navigation carries the name of its entry as title
			workspaceNavigation().findAllByRole('link').should(($links) => {
				const names = [...$links].map((link) => link.title).filter((title) => title.startsWith(tag))
				expect(names).to.deep.equal(sortedNames.map(spaceName))
			})

			// 7. The arrow lists the groups of the workspace. The browser shows the title of each
			// group on hover, a tooltip Cypress cannot see: the test checks the title
			const { alpha } = spaces
			navigationEntry(alpha.name).findByRole('button', { name: label.openMenu }).click()
			navigationEntry(alpha.name).findByRole('button', { name: label.collapseMenu }).should('be.visible')
			workspaceNavigation().contains(label.workspaceGroups).should('be.visible')
			for (const group of spaceGroups(alpha.id, alpha.name)) {
				workspaceNavigation()
					.findByRole('link', { name: group.name })
					.should('be.visible')
					.and('have.attr', 'title', group.name)
			}
		})
	})
}
