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

// A General Manager who sees more workspaces than the navigation loads at first opens the pages
// of one beyond them from their URL, as from a link or a reload

import { LIMIT_WORKSPACES_PER_PAGE } from '../../../src/constants.js'
import {
	createGeneralManager,
	createSpace,
	requestAs,
	serverPath,
	userGroupGid,
	userGroupName,
} from '../../support/helpers.js'

let generalManager
let space
// The only workspace of the first page
let otherSpace
let stranger

before(() => {
	createGeneralManager('en').then((user) => {
		generalManager = user
		createSpace(generalManager).then((created) => {
			space = created
		})
		createSpace(generalManager).then((created) => {
			otherSpace = created
		})
	})
	cy.createRandomUser().then((user) => {
		stranger = user
	})
})

describe('Open a workspace from its URL', () => {
	// Opens a page of the app from its URL. The first page of workspaces the navigation loads
	// leaves the workspace out, as if more than a page of others came before it, and only holds
	// another one: the app must not open the page of that single workspace instead
	const visitBeyondFirstPage = (path) => {
		cy.intercept(
			{ method: 'GET', pathname: '**/apps/workspace/workspaces', query: { limit: String(LIMIT_WORKSPACES_PER_PAGE) } },
			// Changed in place: Cypress ignores a new body
			(req) => req.continue((res) => {
				Object.keys(res.body)
					.filter((name) => name !== otherSpace.name)
					.forEach((name) => delete res.body[name])
			}),
		).as('firstPage')
		cy.login(generalManager)
		cy.visit(path)
		cy.wait('@firstPage')
	}

	// Checks, once the page shows, that the app did not leave it for that of the other workspace
	const shouldStayOn = (path) => cy.location('pathname').should('equal', serverPath(path))

	it('opens the page of the workspace', () => {
		const path = `/apps/workspace/workspace/${space.id}`
		visitBeyondFirstPage(path)
		cy.get('.space-name .titles-for-space').should('contain', space.name)
		shouldStayOn(path)
	})

	it('opens the page of a group of the workspace', () => {
		const path = `/apps/workspace/group/${space.id}/${userGroupGid(space.id)}`
		visitBeyondFirstPage(path)
		cy.get('.group-name .titles-for-space').invoke('text').invoke('trim').should('equal', userGroupName(space.name))
		shouldStayOn(path)
	})

	it('opens the hub of the workspace', () => {
		const path = `/apps/workspace/hub/${space.id}`
		visitBeyondFirstPage(path)
		cy.findByRole('heading', { level: 1, name: space.name }).should('be.visible')
		shouldStayOn(path)
	})

	it('gives the workspace to a General Manager and not to a user who is not a member', () => {
		const url = `/apps/workspace/workspaces/${space.id}`
		requestAs(generalManager, 'GET', url).then(({ status, body }) => {
			expect(status).to.equal(200)
			expect(Object.keys(body)).to.deep.equal([space.name])
		})
		requestAs(stranger, 'GET', url).its('status').should('equal', 404)
	})
})
