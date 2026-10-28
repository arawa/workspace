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
	admin,
	createGeneralManagerWithSpace,
	searchAccountsGroups,
	spaceGroups,
	teamFolderRow,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager reads, in each language the tests run in
const labels = {
	en: {
		deleteSpace: 'Delete space',
		question: (space) => `Are you sure you want to delete the ${space} workspace?`,
		cancel: 'Cancel',
		deleteWorkspace: 'Delete workspace',
	},
	// Both delete buttons read the same in French
	fr: {
		deleteSpace: 'Supprimer le workspace',
		question: (space) => `Êtes vous sûr de vouloir supprimer le workspace ${space} ?`,
		cancel: 'Annuler',
		deleteWorkspace: 'Supprimer le workspace',
	},
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace deletion (${language})`, () => {
		let generalManager
		let space

		before(() => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
			})
		})

		it('lets a General Manager delete a workspace from its page', () => {
			cy.login(generalManager)
			cy.visit(`/apps/workspace/workspace/${space.id}`)

			cy.get('.space-actions')
				.findByRole('button', { name: label.deleteSpace })
				.click()

			// 1. The modal asks to confirm the deletion of this workspace
			cy.findByRole('dialog').as('modal')
			cy.get('@modal')
				.findByRole('heading', { name: label.question(space.name) })
				.should('be.visible')
			cy.get('@modal')
				.findByRole('button', { name: label.cancel })
				.should('be.visible')
			cy.get('@modal')
				.findByRole('button', { name: label.deleteWorkspace })
				.click()

			// 2. The modal closes and the app goes back to its home page
			cy.findByRole('dialog').should('not.exist')
			cy.url().should('match', /\/apps\/workspace\/?$/)

			// 3. The navigation no longer lists the workspace
			workspaceNavigation()
				.findByRole('link', { name: space.name })
				.should('not.exist')
		})

		it('removes the workspace groups from the Accounts page', () => {
			cy.login(admin)
			cy.visit('/settings/users')

			// Neither the server nor the page finds the groups anymore
			searchAccountsGroups(space.name).should('be.empty')
			for (const group of spaceGroups(space.id, space.name)) {
				cy.findByRole('link', { name: group.name }).should('not.exist')
			}
		})

		it('removes the workspace from the Team folders admin settings', () => {
			cy.login(admin)
			cy.visit('/settings/admin/groupfolders')

			// The folder created in cypress.config.js shows once the list is rendered,
			// so the check below cannot pass on an empty table
			teamFolderRow('Existing Team folder').should('exist')
			cy.contains('tr', space.name).should('not.exist')
		})
	})
}
