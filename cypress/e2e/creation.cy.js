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
 *
 */

import {
	admin,
	createGeneralManager,
	managerGroupName,
	randomName,
	randomSuffix,
	searchAccountsGroups,
	teamFolderRow,
	userGroupName,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager reads, in each language the tests run in
const labels = {
	en: {
		newWorkspace: 'New workspace',
		confirm: 'Confirm changes',
		edit: 'Edit the workspace',
		delete: 'Delete space',
		duplicateTitle: 'Error - Duplicate space name',
		duplicateMessage: [
			'This space or groupfolder already exists. Please, use another space name.',
			'If a "toto" space exists, you cannot create the "tOTo" space.',
			'Please check also the groupfolder doesn\'t exist.',
		],
		forbiddenTitle: 'Error creating workspace',
		forbiddenMessage: 'Your Workspace name must not contain the following characters: / : * ? " < > | \\',
	},
	fr: {
		newWorkspace: 'Nouveau workspace',
		confirm: 'Confirmer les modifications',
		edit: 'Éditer le workspace',
		delete: 'Supprimer le workspace',
		duplicateTitle: 'Erreur - Nom de workspace en doublon',
		duplicateMessage: [
			'Ce workspace ou ce dossier de groupe existe déjà. Veuillez utiliser un autre nom de workspace.',
			'Si un workspace « toto » existe, vous ne pouvez pas créer l\'espace « tOTo ».',
			'Vérifiez également que le dossier de groupe n\'existe pas.',
		],
		forbiddenTitle: 'Erreur lors de la création du workspace',
		// French typography: a non-breaking space before ":"
		forbiddenMessage: 'Le nom de votre workspace ne doit pas contenir les caractères suivants\u00a0: / : * ? " < > | \\',
	},
}

// The user counter of a navigation entry, read on the entry's own row: an expanded
// workspace also holds its groups, each with a counter of its own
const userCount = (link) => link
	.closest('.app-navigation-entry')
	.find('.user-counter')
	.invoke('text')

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace creation (${language})`, () => {
		let generalManager
		// Set by the first test, which creates the workspace the other tests check
		let name

		before(() => {
			createGeneralManager(language).then((user) => {
				generalManager = user
			})
		})

		// Submits a new workspace from the navigation, from the home page of the app
		const submitNewWorkspace = (workspaceName) => {
			cy.login(generalManager)
			cy.visit('/apps/workspace')

			cy.findByRole('button', { name: label.newWorkspace }).click()
			cy.findByPlaceholderText(label.newWorkspace).type(workspaceName)
			cy.findByRole('button', { name: label.confirm }).click()
		}

		it('lets a General Manager create a workspace from the navigation', () => {
			name = randomName()
			submitNewWorkspace(name)

			// 1. The app opens the page of the new workspace
			cy.url().should('match', /\/apps\/workspace\/workspace\/\d+$/)

			// 2. Its header shows the color dot and the name, then the actions on the right:
			// the "+" menu, the pencil (edit) and the trash (delete)
			cy.get('.space-name .color-dot')
				.should('have.attr', 'style')
				.and('contain', 'background-color')
			cy.get('.space-name .titles-for-space').should('contain', name)

			cy.get('.space-actions').within(() => {
				cy.get('.icon-add').should('be.visible')
				cy.findByRole('button', { name: label.edit })
					.find('.icon-rename')
					.should('be.visible')
				cy.findByRole('button', { name: label.delete })
					.find('.icon-delete')
					.should('be.visible')
			})

			cy.get('.space-name .titles-for-space').then(($title) => {
				const titleRight = $title[0].getBoundingClientRect().right
				cy.get('.space-actions .icon-add')
					.should(($add) => expect($add[0].getBoundingClientRect().left).to.be.greaterThan(titleRight))
			})

			// 3. The navigation lists the workspace with 0 users
			workspaceNavigation()
				.findByRole('link', { name })
				.as('workspaceLink')
			userCount(cy.get('@workspaceLink')).should('match', /^\s*0\s*$/)

			// 4. Under the workspace, its manager and user groups, each with 0 users
			cy.get('@workspaceLink').closest('li').within(() => {
				for (const group of [managerGroupName(name), userGroupName(name)]) {
					userCount(cy.findByRole('link', { name: group })).should('match', /^\s*0\s*$/)
				}
			})

			// 5. The "New workspace" entry is back to its button, its text field closed
			cy.findByRole('button', { name: label.newWorkspace }).should('be.visible')
			cy.findByPlaceholderText(label.newWorkspace).should('not.exist')
		})

		it('refuses a second workspace with the same name', () => {
			submitNewWorkspace(name)

			// The server answers 409 and the app shows its title and message in an error toast
			cy.contains('p', label.duplicateTitle)
				.should('be.visible')
				.parent()
				.should('contain', label.duplicateMessage[0])
				.and('contain', label.duplicateMessage[1])
				.and('contain', label.duplicateMessage[2])
		})

		// A sample of WorkspaceCheckService::CHARACTERS_SPECIAL
		for (const character of ['/', ':', '<', '\\']) {
			it(`refuses a workspace name containing "${character}"`, () => {
				const forbiddenName = `Workspace ${character} ${randomSuffix()}`
				submitNewWorkspace(forbiddenName)

				// The server answers 400 and the app shows its title and message in an error toast
				cy.contains('p', label.forbiddenTitle)
					.should('be.visible')
					.parent()
					.should('contain', label.forbiddenMessage)

				workspaceNavigation()
					.findByRole('link', { name: forbiddenName })
					.should('not.exist')
			})
		}

		it('lists the workspace groups on the Accounts page', () => {
			cy.login(admin)
			cy.visit('/settings/users')
			searchAccountsGroups(name)

			for (const group of [managerGroupName(name), userGroupName(name)]) {
				cy.findByRole('link', { name: group }).should('be.visible')
			}
		})

		it('lists the workspace in the Team folders admin settings', () => {
			cy.login(admin)
			cy.visit('/settings/admin/groupfolders')

			teamFolderRow(name).within(() => {
				// Groups column: the manager and user groups
				cy.get('td.groups')
					.should('contain', managerGroupName(name))
					.and('contain', userGroupName(name))

				// Advanced permissions column: enabled, and managed by the manager group
				cy.findByRole('checkbox', { name: `Advanced permissions for "${name}"` })
					.should('be.checked')
				cy.get('td.acl').should('contain', managerGroupName(name))
			})
		})
	})
}
