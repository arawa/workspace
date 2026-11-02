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

// A General Manager or a Workspace Manager assigns a member as Workspace Manager of a workspace,
// then removes their rights, from the actions menu of the member on the workspace page

import {
	addSpaceManager,
	admin,
	createGeneralManager,
	createSpace,
	createSpaceMember,
	managerGroupGid,
	managerGroupName,
	setDisplayName,
	shouldListInAccountsGroup,
	shouldNotListInAccountsGroup,
	userGroupGid,
	userGroupName,
	WORKSPACES_MANAGERS,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The trigger of the menu belongs to @nextcloud/vue
const labels = {
	en: {
		actions: 'Actions',
		assignManager: 'Assign as WM',
		removeManager: 'Remove WM rights',
	},
	fr: {
		actions: 'Actions',
		assignManager: 'Assigner comme WM',
		removeManager: 'Retirer les droits de WM',
	},
}

// The background of a row that has none
const TRANSPARENT = 'rgba(0, 0, 0, 0)'

// The row of a user in the table of users, found by their whole display name
const userRow = (name) => cy.contains('.table-space-detail tbody .user-name', new RegExp(`^\\s*${name}\\s*$`))
	.closest('tr')

// Clicks an action of the menu of the user, and waits for the server to change their role
const clickAction = (label, name, action) => {
	cy.intercept({ method: 'PATCH', pathname: '**/apps/workspace/api/space/*/user/*' }).as('changeRole')
	userRow(name).findByRole('button', { name: label.actions }).click()
	cy.findByRole('menuitem', { name: action }).click()
	cy.wait('@changeRole').its('response.statusCode').should('equal', 200)
}

// The display names of the highlighted rows of the Workspace Managers, in their order
const shouldListManagers = (names) => cy.get('.table-space-detail tbody tr.user-admin .user-name')
	.should(($names) => {
		expect([...$names].map((name) => name.textContent.trim())).to.deep.equal(names)
	})

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace Manager role (${language})`, () => {
		let generalManager
		let space
		let otherSpace
		// The Workspace Manager who logs in
		let manager
		// A member who becomes Workspace Manager, then a member again
		let cleo
		// A Workspace Manager of this workspace and of another one
		let eve

		// A new member of the workspace, with the display name the table sorts them by
		const createMember = (displayName) => createSpaceMember(generalManager, space, language).then((user) => {
			setDisplayName(user, displayName)
			return cy.wrap(user)
		})

		// Each role gets its own workspaces, so the second one does not find the first one's changes
		const setUp = () => {
			createGeneralManager(language).then((user) => {
				generalManager = user
				createSpace(generalManager).then((created) => {
					space = created
				})
				createSpace(generalManager).then((created) => {
					otherSpace = created
				})
			})
			cy.then(() => {
				// Workspace Managers already there, before and after Cleo in alphabetical order
				createMember('Bea').then((user) => addSpaceManager(generalManager, space, user))
				createMember('Dan').then((user) => addSpaceManager(generalManager, space, user))
				createMember('Zoé').then((user) => {
					manager = user
					addSpaceManager(generalManager, space, manager)
				})
				createMember('Cleo').then((user) => {
					cleo = user
				})
				createMember('Eve').then((user) => {
					eve = user
					addSpaceManager(generalManager, space, eve)
					addSpaceManager(generalManager, otherSpace, eve)
				})
			})
		}

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				before(setUp)

				const openSpace = () => {
					cy.login(user())
					cy.visit(`/apps/workspace/workspace/${space.id}`)
				}

				it('assigns a member as Workspace Manager', () => {
					openSpace()
					clickAction(label, 'Cleo', label.assignManager)

					// The row is highlighted, among the Workspace Managers sorted by name. The class
					// keeps the check right while the pointer is over the row, which changes its color
					userRow('Cleo')
						.should('have.class', 'user-admin')
						.and('not.have.css', 'background-color', TRANSPARENT)
					shouldListManagers(['Bea', 'Cleo', 'Dan', 'Eve', 'Zoé'])
					userRow('Cleo').find('.group-list')
						.should('have.text', `${userGroupName(space.name)}, ${managerGroupName(space.name)}`)
				})

				it('lists the new Workspace Manager in the manager group and in WorkspacesManagers', () => {
					cy.login(admin)

					shouldListInAccountsGroup(userGroupGid(space.id), cleo)
					shouldListInAccountsGroup(managerGroupGid(space.id), cleo)
					shouldListInAccountsGroup(WORKSPACES_MANAGERS, cleo)
				})

				it('removes the rights of a Workspace Manager', () => {
					openSpace()
					clickAction(label, 'Cleo', label.removeManager)

					// The row is a member's again, only in the user group
					userRow('Cleo')
						.should('have.class', 'user-simple')
						.and('have.css', 'background-color', TRANSPARENT)
					shouldListManagers(['Bea', 'Dan', 'Eve', 'Zoé'])
					userRow('Cleo').find('.group-list').should('have.text', userGroupName(space.name))
				})

				it('removes them from the manager group and from WorkspacesManagers', () => {
					cy.login(admin)

					shouldListInAccountsGroup(userGroupGid(space.id), cleo)
					shouldNotListInAccountsGroup(managerGroupGid(space.id), cleo)
					shouldNotListInAccountsGroup(WORKSPACES_MANAGERS, cleo)
				})

				it('keeps in WorkspacesManagers a Workspace Manager of another workspace', () => {
					openSpace()
					clickAction(label, 'Eve', label.removeManager)

					cy.login(admin)
					shouldNotListInAccountsGroup(managerGroupGid(space.id), eve)
					shouldListInAccountsGroup(managerGroupGid(otherSpace.id), eve)
					shouldListInAccountsGroup(WORKSPACES_MANAGERS, eve)
				})
			})
		}
	})
}
