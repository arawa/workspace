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
	createSpaceMember,
	serverPath,
} from '../support/helpers.js'

// What a member reads, in each language the tests run in
const labels = {
	en: { users: 'Users' },
	fr: { users: 'Utilisateurs' },
}

// Shared by every test: the General Manager only creates them, in no language
let generalManager
// The member is in the first two, not in the third. With two workspaces, the app
// does not open the page of a single one
let spaces

before(() => {
	createGeneralManager('en').then((user) => {
		generalManager = user
		spaces = []
		for (let i = 0; i < 3; i++) {
			createSpace(generalManager).then((space) => {
				spaces.push(space)
			})
		}
	})
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace hub (${language})`, () => {
		let member

		before(() => {
			createSpaceMember(generalManager, spaces[0], language).then((user) => {
				member = user
				addSpaceMember(generalManager, spaces[1], member)
			})
		})

		it('shows the hub of a workspace of the member, opened from its URL', () => {
			cy.login(member)
			cy.visit(`/apps/workspace/hub/${spaces[1].id}`)

			cy.findByRole('heading', { level: 1, name: spaces[1].name }).should('be.visible')
			cy.get('.hub-home').should('contain', label.users)
		})

		it('opens the list of workspaces instead of the hub of a workspace the member is not in', () => {
			cy.login(member)
			cy.visit(`/apps/workspace/hub/${spaces[2].id}`)

			cy.location('pathname').should('equal', serverPath('/apps/workspace/'))
			cy.get('.table-spaces')
				.should('contain', spaces[0].name)
				.and('contain', spaces[1].name)
			cy.contains(spaces[2].name).should('not.exist')
		})
	})
}
