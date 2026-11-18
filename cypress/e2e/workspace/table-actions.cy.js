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
	clickMenuButton,
	createGeneralManager,
	createSpace,
	createSpaceManager,
	editModal,
	menuButtons,
	randomName,
	randomSuffix,
	saveEditModal,
	searchWorkspaces,
	workspaceNavigation,
} from '../../support/helpers.js'

// What a General Manager reads, in each language the tests run in
const labels = {
	en: {
		actions: 'Actions',
		edit: 'Edit the workspace',
		rename: 'Rename your workspace',
		deleteSpace: 'Delete space',
		question: (space) => `Are you sure you want to delete the ${space} workspace?`,
		deleteWorkspace: 'Delete workspace',
	},
	fr: {
		actions: 'Actions',
		edit: 'Éditer le workspace',
		rename: 'Renommer votre workspace',
		deleteSpace: 'Supprimer le workspace',
		question: (space) => `Êtes vous sûr de vouloir supprimer le workspace ${space} ?`,
		deleteWorkspace: 'Supprimer le workspace',
	},
}

const workspaceRow = (name) => cy.contains('.table-spaces tr', name)

// The menu of the row, the "meatballs" button of its Actions column
const rowMenu = (name, label) => workspaceRow(name).findByRole('button', { name: label.actions })

for (const [language, label] of Object.entries(labels)) {
	describe(`Actions of the table of all workspaces (${language})`, () => {
		// Shared by the names of the workspaces, so the tests find their own among those of the other specs
		const tag = randomSuffix()
		let generalManager
		let toRename
		let toDelete

		before(() => {
			createGeneralManager(language).then((user) => {
				generalManager = user
				createSpace(generalManager, `${tag} rename`).then((space) => {
					toRename = space
				})
				createSpace(generalManager, `${tag} delete`).then((space) => {
					toDelete = space
				})
			})
		})

		beforeEach(() => {
			cy.login(generalManager)
			cy.visit('/apps/workspace/')
			searchWorkspaces(language, tag)
		})

		it('renames a workspace from its row', () => {
			const newName = `${tag} ${randomName()}`

			rowMenu(toRename.name, label).click()
			clickMenuButton(label.edit)

			// The menu closes behind the modal
			menuButtons().should('not.exist')
			editModal(language)
				.findByPlaceholderText(label.rename)
				.as('nameField')
				.should('have.value', toRename.name)
			cy.get('@nameField').clear()
			cy.get('@nameField').type(newName)
			saveEditModal(language)

			// The table stays on the list and shows the new name only
			cy.location('pathname').should('match', /\/apps\/workspace\/?$/)
			workspaceRow(newName).should('be.visible')
			cy.contains('.table-spaces tr', toRename.name).should('not.exist')
		})

		it('deletes a workspace from its row, after a confirmation', () => {
			rowMenu(toDelete.name, label).click()
			clickMenuButton(label.deleteSpace)

			cy.findByRole('dialog', { name: label.question(toDelete.name) })
				.findByRole('button', { name: label.deleteWorkspace })
				.click()

			// The modal closes, the table stays on the list without the workspace, and so does the navigation
			cy.findByRole('dialog').should('not.exist')
			cy.location('pathname').should('match', /\/apps\/workspace\/?$/)
			cy.contains('.table-spaces tr', toDelete.name).should('not.exist')
			workspaceNavigation().findByRole('link', { name: toDelete.name }).should('not.exist')
		})

		it('gives a Workspace Manager no actions', () => {
			// Of two workspaces: with a single one, the app would open it instead of the table
			createSpace(generalManager, `${tag} first managed`).then((first) => {
				createSpace(generalManager, `${tag} second managed`).then((second) => {
					createSpaceManager(generalManager, first, language).then((manager) => {
						addSpaceManager(generalManager, second, manager)
						cy.login(manager)
						cy.visit('/apps/workspace/')

						cy.get('.table-spaces tbody tr').should('have.length', 2)
						cy.get('.table-spaces').findByRole('button', { name: label.actions }).should('not.exist')
					})
				})
			})
		})
	})
}
