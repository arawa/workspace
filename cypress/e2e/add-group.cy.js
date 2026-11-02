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
	addSpaceManager,
	createGeneralManager,
	createSpace,
	navigationEntry,
	randomSuffix,
	searchWorkspaces,
	setLanguage,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The arrow of an entry belongs to @nextcloud/vue
const labels = {
	en: {
		openMenu: 'Open menu',
		addedGroups: 'Added groups',
		addGroup: 'Add a group',
		searchGroups: 'Start typing text to search for groups',
		noGroup: 'No group selected',
		add: 'Add',
	},
	fr: {
		openMenu: 'Ouvrir le menu',
		addedGroups: 'Groupes ajoutés',
		addGroup: 'Ajouter un groupe',
		searchGroups: 'Commencez à taper du texte pour rechercher des groupes',
		noGroup: 'Aucun groupe sélectionné',
		add: 'Ajouter',
	},
}

// Shared by the names of the workspaces, so the search keeps those of this spec
const tag = randomSuffix()

// Shared by every test: each language only changes that of the users who log in
let generalManager
let workspaceManager
// The workspace whose entry the tests unfold
let space

before(() => {
	// Its language is set by each language below
	cy.createRandomUser().then((user) => {
		workspaceManager = user
	})
	// The Workspace Manager manages both workspaces: with a single one, the app would open
	// its page, which unfolds its entry already
	createGeneralManager('en').then((user) => {
		generalManager = user
		createSpace(generalManager, `${tag} first`).then((created) => {
			space = created
			addSpaceManager(generalManager, space, workspaceManager)
		})
		createSpace(generalManager, `${tag} second`).then((second) => {
			addSpaceManager(generalManager, second, workspaceManager)
		})
	})
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Add a group from the navigation (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
			setLanguage(workspaceManager, language)
		})

		// Unfolds the workspace in the navigation, then opens the modal from the "+" of its
		// added groups, which must read in the language of the user
		const openAddGroupModal = () => {
			// 1. Unfolding the workspace shows the "+" of its added groups, which opens the modal
			navigationEntry(space.name).findByRole('button', { name: label.openMenu }).click()
			workspaceNavigation().contains(label.addedGroups).should('be.visible')
			workspaceNavigation().findByRole('button', { name: label.addGroup }).click()

			// 2. The modal is named by its title, its only heading: a name passed to NcModal
			// would add a second one, hidden but read by screen readers
			cy.findByRole('dialog', { name: label.addGroup }).within(() => {
				cy.findByRole('heading').should('be.visible')
				cy.findByPlaceholderText(label.searchGroups).should('be.visible')
				cy.contains(label.noGroup).should('be.visible')
				cy.findByRole('button', { name: label.add }).should('be.visible')
			})
		}

		it('opens the modal to add a group for a General Manager', () => {
			cy.login(generalManager)
			cy.visit('/apps/workspace/')
			// A General Manager sees the workspaces of every spec
			searchWorkspaces(language, tag)
			openAddGroupModal()
		})

		it('opens the modal to add a group for a Workspace Manager', () => {
			cy.login(workspaceManager)
			cy.visit('/apps/workspace/')
			openAddGroupModal()
		})
	})
}
