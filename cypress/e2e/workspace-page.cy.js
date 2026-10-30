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

import { User } from '@nextcloud/e2e-test-server/cypress'
import {
	addGroupMember,
	addSpaceManager,
	addSpaceMember,
	createGeneralManager,
	createSpace,
	createSubgroup,
	managerGroupName,
	modifyUser,
	randomSuffix,
	rgb,
	searchWorkspaces,
	serverPath,
	setLanguage,
	shouldHaveColumns,
	subgroupName,
	userGroupName,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in
const labels = {
	en: {
		users: 'Users',
		unlimited: 'unlimited',
		// The "+" menu, named by @nextcloud/vue
		actions: 'Actions',
		edit: 'Edit the workspace',
		delete: 'Delete space',
		columns: ['', 'Users', 'Role', 'Groups', ''],
		// Not translated
		roles: { manager: 'wm', member: 'user' },
	},
	fr: {
		users: 'Utilisateurs',
		unlimited: 'illimité',
		actions: 'Actions',
		edit: 'Éditer le workspace',
		delete: 'Supprimer le workspace',
		columns: ['', 'Utilisateurs', 'Rôle', 'Groupes', ''],
		roles: { manager: 'wm', member: 'user' },
	},
}

// Shared by the names of the workspaces and the UIDs of the users, so the tests find their
// own among those of the other specs
const tag = randomSuffix()
const taggedUser = (name) => new User(`${name}-${tag}`, `${name}-${tag}`)

// The users of the workspace, with the name the table shows and their subgroups in the order
// it shows them. Without a display name, the table shows the UID
const adam = { user: taggedUser('adam'), displayName: `Adam ${tag}`, manager: true, subgroups: [] }
const zoe = { user: taggedUser('zoe'), displayName: `Zoé ${tag}`, email: `zoe-${tag}@example.com`, manager: true, subgroups: ['beta'] }
const bruno = { user: taggedUser('bruno'), displayName: `Bruno ${tag}`, manager: false, subgroups: ['alpha', 'zeta'] }
const yann = { user: taggedUser('yann'), manager: false, subgroups: [] }
const nameOf = ({ user, displayName }) => displayName ?? user.userId

// The table lists the Workspace Managers first, then the members, each sorted by name. The
// users and the subgroups are added in another order
const tableOrder = [adam, zoe, bruno, yann]
const creationOrder = [zoe, adam, yann, bruno]
const subgroupOrder = ['zeta', 'beta', 'alpha']

// Shared by every test: each language only changes that of the users who log in
let generalManager
// The workspace whose page the tests open
let space

before(() => {
	// Their language is set by each language below
	for (const { user, displayName, email } of creationOrder) {
		cy.createUser(user)
		if (displayName) {
			modifyUser(user, 'displayname', displayName)
		}
		if (email) {
			modifyUser(user, 'email', email)
		}
	}
	// The Workspace Managers manage both workspaces: with a single one, the app would open
	// its page without a click
	createGeneralManager('en').then((created) => {
		generalManager = created
		createSpace(generalManager, `${tag} first`).then((first) => {
			space = first
			for (const { user, manager } of creationOrder) {
				(manager ? addSpaceManager : addSpaceMember)(generalManager, space, user)
			}
			for (const name of subgroupOrder) {
				createSubgroup(generalManager, space, name).then(({ gid }) => {
					creationOrder
						.filter(({ subgroups }) => subgroups.includes(name))
						.forEach(({ user }) => addGroupMember(generalManager, space, gid, user))
				})
			}
		})
		createSpace(generalManager, `${tag} second`).then((second) => {
			creationOrder
				.filter(({ manager }) => manager)
				.forEach(({ user }) => addSpaceManager(generalManager, second, user))
		})
	})
})

// A button of the header of the workspace page, next to its name
const spaceAction = (name) => cy.get('.space-actions').findByRole('button', { name })

// The rows of the table of users, and their cells in the order of the columns: avatar,
// user, role, groups, actions
const userRows = () => cy.get('.table-space-detail tbody tr')
const ROLE = 2
const GROUPS = 3

// Checks the text of an element, without the spaces around it
const shouldHaveText = (subject, text) => subject.invoke('text').invoke('trim').should('equal', text)

// The groups a user shows: the user group, then the manager group, then the subgroups
const groupsOf = ({ manager, subgroups }) => [
	userGroupName(space.name),
	...(manager ? [managerGroupName(space.name)] : []),
	...subgroups.map((name) => subgroupName(name, space.name)),
].join(', ')

// The background of a row of a Workspace Manager: the light primary color of the theme, as
// the browser computes it
const primaryLight = () => cy.document().then((doc) => {
	const probe = doc.createElement('div')
	probe.style.backgroundColor = 'var(--color-primary-light)'
	doc.body.appendChild(probe)
	const color = doc.defaultView.getComputedStyle(probe).backgroundColor
	probe.remove()
	return color
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace page (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
			setLanguage(zoe.user, language)
		})

		// Opens the workspace from the navigation, then its page from the "Users" tile of its
		// hub. Checks the color, name and quota of its header, its "+" menu and the columns
		// of its table of users
		const openWorkspacePage = () => {
			workspaceNavigation().findByRole('link', { name: space.name }).click()
			cy.get('.hub-home').findByRole('link', { name: label.users }).click()
			cy.location('pathname').should('equal', serverPath(`/apps/workspace/workspace/${space.id}`))

			cy.get('.space-name').within(() => {
				cy.get('.color-dot').should('have.css', 'background-color', rgb(space.color))
				cy.get('.titles-for-space').should('contain', space.name)
				cy.get('.quota-bubble').should('have.text', label.unlimited)
			})
			spaceAction(label.actions).should('be.visible')
			shouldHaveColumns('.table-space-detail', label.columns)
		}

		// The users show in the order of the table, with their avatar, either a picture or
		// initials, their name, their email, empty when they have none, their role and their
		// groups. The rows of the Workspace Managers have the light primary color. Their hover
		// color is left out: CSS :hover needs a real pointer, which Cypress does not move in
		// Firefox
		const checkUsers = () => {
			userRows().should('have.length', tableOrder.length)
			primaryLight().then((primary) => {
				const background = { manager: primary, member: 'rgba(0, 0, 0, 0)' }
				tableOrder.forEach((entry, index) => {
					const role = entry.manager ? 'manager' : 'member'
					userRows().eq(index).should('have.css', 'background-color', background[role]).within(() => {
						cy.get('.avatardiv img, .avatardiv .avatardiv__initials').should('exist')
						cy.get('.user-name').should('have.text', nameOf(entry))
						shouldHaveText(cy.get('.user-email'), entry.email ?? '')
						shouldHaveText(cy.get('td').eq(ROLE), label.roles[role])
						shouldHaveText(cy.get('td').eq(GROUPS), groupsOf(entry))
					})
				})
			})
		}

		it('shows the workspace, its actions and its users to a General Manager', () => {
			cy.login(generalManager)
			cy.visit('/apps/workspace/')

			// A General Manager finds the workspace among those of every spec
			searchWorkspaces(language, tag)

			// The page shows the header with all its actions, and the table of users
			openWorkspacePage()
			spaceAction(label.edit).should('be.visible')
			spaceAction(label.delete).should('be.visible')

			checkUsers()
		})

		it('shows the workspace and its users to a Workspace Manager, who cannot edit or delete it', () => {
			cy.login(zoe.user)
			cy.visit('/apps/workspace/')

			// The page shows the header and the table of users. Checked once the "+" menu
			// shows, a Workspace Manager has neither the pencil nor the bin
			openWorkspacePage()
			spaceAction(label.edit).should('not.exist')
			spaceAction(label.delete).should('not.exist')

			checkUsers()
		})
	})
}
