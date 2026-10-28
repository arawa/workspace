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

// A simple user is a member of the user group of a workspace, neither General Manager
// nor Workspace Manager

import {
	addSpaceMember,
	createGeneralManager,
	createSpace,
	createSpaceMember,
	createUser,
	findSpace,
	ocsUrl,
	randomName,
	requestAs,
	searchSpaces,
	serverPath,
	workspaceNavigation,
} from '../support/helpers.js'

// What a simple user reads, in each language the tests run in
const labels = {
	en: {
		allWorkspaces: 'All workspaces',
		newWorkspace: 'New workspace',
		noWorkspace: 'No workspace found',
		noWorkspaceDescription: 'You aren\'t in any workspaces. Please contact a General Manager.',
	},
	fr: {
		allWorkspaces: 'Tous les workspaces',
		newWorkspace: 'Nouveau workspace',
		noWorkspace: 'Aucun workspace trouvé',
		noWorkspaceDescription: 'Vous n\'êtes dans aucun workspace. Veuillez contacter General Manager.',
	},
}

// The counter of the "All workspaces" entry of the navigation
const allWorkspacesCount = (allWorkspaces) => workspaceNavigation()
	.findByRole('link', { name: allWorkspaces })
	.closest('.app-navigation-entry')
	.find('.counter-bubble__counter')

// Shared by every test. The General Manager only sets up the workspaces, so its language does not matter
let generalManager
let space
// A workspace the members are not in
let otherSpace

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
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Simple user (${language})`, () => {
		let member
		// A user in no workspace at all
		let outsider

		before(() => {
			createSpaceMember(generalManager, space, language).then((user) => {
				member = user
			})
			createUser(language).then((user) => {
				outsider = user
			})
		})

		it('only lists the workspaces the user is a member of', () => {
			cy.login(member)
			cy.visit('/apps/workspace')

			// 1. With a single workspace, the app opens its page
			cy.location('pathname').should('equal', serverPath(`/apps/workspace/workspace/${space.id}`))

			// 2. The navigation lists the workspace of the user, and counts it
			workspaceNavigation().findByRole('link', { name: space.name }).should('be.visible')
			allWorkspacesCount(label.allWorkspaces).should('have.text', '1')

			// 3. The table of all workspaces lists it too
			workspaceNavigation().findByRole('link', { name: label.allWorkspaces }).click()
			cy.get('.table-spaces').should('contain', space.name)

			// 4. Neither lists the other workspace, checked once the lists are loaded
			workspaceNavigation().findByRole('link', { name: otherSpace.name }).should('not.exist')
			cy.get('.table-spaces').should('not.contain', otherSpace.name)

			// 5. The user cannot create a workspace
			cy.findByRole('button', { name: label.newWorkspace }).should('not.exist')
		})

		it('tells a user who is a member of no workspace to contact a General Manager', () => {
			cy.login(outsider)
			cy.visit('/apps/workspace')

			cy.location('pathname').should('equal', serverPath('/apps/workspace/not-found'))
			cy.contains(label.noWorkspace).should('be.visible')
			cy.contains(label.noWorkspaceDescription).should('be.visible')
			allWorkspacesCount(label.allWorkspaces).should('have.text', '0')
			workspaceNavigation().findByRole('link', { name: space.name }).should('not.exist')
		})
	})
}

// The server refuses each change a simple user would send by hand: the pages hide these
// actions, which does not stop a request. Runs once, the language changes nothing there
describe('Simple user on the server', () => {
	const color = '#ff0000'
	const quota = 1073741824
	let member
	// The fields of the workspace the requests would change, as the server saved them before
	// the request of the member. The other fields, such as its size, change on their own
	let savedSpace

	// The fields of the workspace the requests would change
	const changedFields = ({ name, color, quota }) => ({ name, color, quota })

	before(() => {
		createSpaceMember(generalManager, space, 'en').then((user) => {
			member = user
		})
	})

	// Read before each test, so a change the server wrongly accepts only fails its own test
	beforeEach(() => {
		findSpace(generalManager, space).then((found) => {
			savedSpace = changedFields(found)
		})
	})

	// Each change through the routes of appinfo/routes.php, used by the pages, then through the OCS API
	const changes = {
		'the pages': {
			'rename the workspace': () => ['PATCH', `/apps/workspace/spaces/${space.id}`, { newSpaceName: randomName() }],
			'change the color of the workspace': () => ['POST', `/apps/workspace/workspaces/${space.id}/color`, { colorCode: color }],
			'change the quota of the workspace': () => ['POST', `/apps/workspace/spaces/${space.id}/quota`, { quota }],
		},
		'the OCS API': {
			'rename the workspace': () => ['PATCH', ocsUrl(`/spaces/${space.id}`), { name: randomName() }],
			'change the color of the workspace': () => ['PATCH', ocsUrl(`/spaces/${space.id}`), { color }],
			'change the quota of the workspace': () => ['PATCH', ocsUrl(`/spaces/${space.id}`), { quota }],
		},
	}

	for (const [api, requests] of Object.entries(changes)) {
		for (const [change, request] of Object.entries(requests)) {
			it(`refuses a member to ${change} through ${api}`, () => {
				requestAs(member, ...request()).its('status').should('equal', 403)
				findSpace(generalManager, space).then(changedFields).should('deep.equal', savedSpace)
			})
		}
	}

	// The deletion of a workspace made for each test, so one the server wrongly deletes leaves
	// the other tests their workspace
	const deletions = {
		'the pages': (id) => ['DELETE', `/apps/workspace/spaces/${id}`],
		'the OCS API': (id) => ['DELETE', ocsUrl(`/spaces/${id}`)],
	}

	for (const [api, request] of Object.entries(deletions)) {
		it(`refuses a member to delete the workspace through ${api}`, () => {
			createSpace(generalManager).then((deleted) => {
				addSpaceMember(generalManager, deleted, member)
				requestAs(member, ...request(deleted.id)).its('status').should('equal', 403)
				findSpace(generalManager, deleted).its('id').should('equal', deleted.id)
			})
		})
	}

	it('refuses a member to create a workspace through the OCS API', () => {
		const name = randomName()
		requestAs(member, 'POST', ocsUrl('/spaces'), { name }).its('status').should('equal', 403)
		searchSpaces(generalManager, name).should('be.empty')
	})
})
