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

export const admin = new User('admin', 'admin')

// cy.runOccCommand() of @nextcloud/e2e-test-server 0.6 passes the whole string as a single
// argument, so occ sees "group:adduser GeneralManager bob" as the command name
const occ = (...args) => cy.runCommand(['php', 'occ', ...args], { verbose: true })

// A random suffix keeps a test independent from any other spec creating workspaces
export const randomSuffix = () => Math.random().toString(36).slice(2, 10)
export const randomName = () => `Workspace ${randomSuffix()}`

// cy.request() as the given user on the OCS API of the app, yields ocs.data
const ocs = (user, method, path, body) => cy.request({
	method,
	// baseUrl ends with /index.php, OCS lives next to it
	url: Cypress.config('baseUrl').replace(/\/index\.php\/?$/, `/ocs/v2.php/apps/workspace/api/v1${path}`),
	auth: { user: user.userId, pass: user.password },
	headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' },
	body,
}).its('body.ocs.data')

// A new General Manager who reads the app in the given language ("en" or "fr")
export const createGeneralManager = (language) => cy.createRandomUser().then((generalManager) => {
	occ('group:adduser', 'GeneralManager', generalManager.userId)
	cy.modifyUser(generalManager, 'language', language)
	return cy.wrap(generalManager)
})

// A new General Manager, in the given language, with a workspace and a "maison" subgroup in it,
// created through the OCS API since the creation itself is covered by creation.cy.js.
// Yields { generalManager, space }
export const createGeneralManagerWithSpace = (language) => createGeneralManager(language).then((generalManager) => {
	return ocs(generalManager, 'POST', '/spaces', { name: randomName() }).then((space) => {
		// A subgroup, whose display name also carries the workspace name
		ocs(generalManager, 'POST', `/spaces/${space.id}/groups`, { name: 'maison' })
		return cy.wrap({ generalManager, space })
	})
})

// Display names of the manager and user groups of a workspace. An admin can change their
// prefixes, which cypress.config.js reads from the server
export const managerGroupName = (spaceName) => `${Cypress.expose('displayPrefixManagerGroup')}${spaceName}`
export const userGroupName = (spaceName) => `${Cypress.expose('displayPrefixUserGroup')}${spaceName}`

// The three groups of a workspace created by createGeneralManagerWithSpace():
// display names follow the workspace name, GIDs its id
export const spaceGroups = (spaceId, spaceName) => [
	{ name: managerGroupName(spaceName), gid: `SPACE-GE-${spaceId}` },
	{ name: userGroupName(spaceName), gid: `SPACE-U-${spaceId}` },
	{ name: `G-maison-${spaceName}`, gid: `SPACE-G-maison-${spaceId}` },
]

// The navigation of the app, whose label is not translated
export const workspaceNavigation = () => cy.findByRole('navigation', { name: 'workspace navigation' })

// Searches the groups of the Accounts page, which loads them 25 at a time: without a search,
// a group further down the list would not show. Yields the groups the server found.
export const searchAccountsGroups = (search) => {
	cy.intercept({ method: 'GET', pathname: '**/cloud/groups/details', query: { search } }).as('groupSearch')
	cy.findByLabelText('Search accounts and groups…').as('accountsSearch')
	cy.get('@accountsSearch').clear()
	cy.get('@accountsSearch').type(search)
	return cy.wait('@groupSearch').its('response.body.ocs.data.groups')
}

// The row of a folder in the Team folders admin settings
export const teamFolderRow = (name) => cy.findByRole('button', { name: `Rename "${name}"` }).closest('tr')

// The edit modal of a workspace, in each language the tests run in
const editModalLabels = {
	en: { title: 'Edit the workspace', save: 'Save' },
	fr: { title: 'Éditer le workspace', save: 'Enregistrer' },
}

// Queried each time rather than aliased: the color picker and the quota select open
// a second dialog or a listbox outside the modal
export const editModal = (language) => cy.contains('[role="dialog"]', editModalLabels[language].title)

// Opens the edit modal from the pencil in the header of the workspace page
export const openEditModal = (generalManager, space, language) => {
	cy.login(generalManager)
	cy.visit(`/apps/workspace/workspace/${space.id}`)

	cy.get('.space-actions')
		.findByRole('button', { name: editModalLabels[language].title })
		.click()
	editModal(language)
		.findByRole('heading', { name: editModalLabels[language].title })
		.should('be.visible')
}

// Saves the edit modal and waits for it to close. Save is found by its text: its
// accessible name comes from aria-label="Save", never translated
export const saveEditModal = (language) => {
	editModal(language).contains('button', editModalLabels[language].save).click()
	cy.findByRole('dialog').should('not.exist')
}
