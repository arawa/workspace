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
	editModal,
	managerGroupName,
	openEditModal,
	randomName,
	saveEditModal,
	searchAccountsGroups,
	serverPath,
	spaceGroups,
	teamFolderRow,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager reads, in each language the tests run in
const labels = {
	en: { rename: 'Rename your workspace' },
	fr: { rename: 'Renommer votre workspace' },
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace renaming (${language})`, () => {
		let generalManager
		// Keeps its old name: only the server and the pages take the new one
		let space
		// Set by the first test, which renames the workspace the other tests check
		let newName

		before(() => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
			})
		})

		it('lets a General Manager rename a workspace from its page', () => {
			newName = randomName()

			// 1. The modal opens with its title and the field to rename the workspace
			openEditModal(generalManager, space, language)
			// The field has no label, only a placeholder
			editModal(language)
				.findByPlaceholderText(label.rename)
				.as('nameField')
				.should('have.value', space.name)
			cy.get('@nameField').clear()
			cy.get('@nameField').type(newName)

			// 2. The modal closes and the page of the same workspace shows its new name
			saveEditModal(language)
			cy.location('pathname').should('equal', serverPath(`/apps/workspace/workspace/${space.id}`))
			cy.get('.space-name .titles-for-space').should('contain', newName)

			// The page renames the groups before the server answers: reloaded so the next
			// steps check the names the server saved
			cy.reload()

			// 3. The navigation lists the workspace under its new name, the old one is gone
			workspaceNavigation().as('navigation')
			cy.get('@navigation').findByRole('link', { name: space.name }).should('not.exist')
			cy.get('@navigation').findByRole('link', { name: newName }).should('be.visible')

			// 4. Each group takes the new name in the navigation and as the title of its page,
			// while its GID, in the URL, does not change
			for (const group of spaceGroups(space.id, newName)) {
				cy.get('@navigation').findByRole('link', { name: group.name }).click()
				cy.location('pathname').should('equal', serverPath(`/apps/workspace/group/${space.id}/${group.gid}`))
				cy.get('.group-name .titles-for-space').should('have.text', group.name)
			}
		})

		it('lists the renamed groups on the Accounts page', () => {
			cy.login(admin)
			cy.visit('/settings/users')

			// Same GIDs, new display names
			searchAccountsGroups(newName)
			for (const group of spaceGroups(space.id, newName)) {
				cy.findByRole('link', { name: group.name })
					.should('be.visible')
					.and('have.prop', 'pathname', serverPath(`/settings/users/${group.gid}`))
			}

			// No group keeps the old workspace name, neither on the server nor on the page
			searchAccountsGroups(space.name).should('be.empty')
			for (const group of spaceGroups(space.id, space.name)) {
				cy.findByRole('link', { name: group.name }).should('not.exist')
			}
		})

		it('lists the renamed workspace in the Team folders admin settings', () => {
			cy.login(admin)
			cy.visit('/settings/admin/groupfolders')

			// The folder takes the new name
			teamFolderRow(newName).within(() => {
				// Groups column: the manager, user and subgroup under their new names
				for (const group of spaceGroups(space.id, newName)) {
					cy.get('td.groups').should('contain', group.name)
				}
				cy.get('td.groups').should('not.contain', space.name)

				// Advanced permissions column: still managed by the renamed manager group
				cy.get('td.acl')
					.should('contain', managerGroupName(newName))
					.and('not.contain', space.name)
			})

			// Checked once the list is loaded, so it cannot pass on an empty page
			cy.findByRole('button', { name: `Rename "${space.name}"` }).should('not.exist')
		})
	})
}
