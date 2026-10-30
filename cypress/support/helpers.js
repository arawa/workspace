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

// The path of a page of the server, behind the /index.php of baseUrl
export const serverPath = (path) => `${new URL(Cypress.config('baseUrl')).pathname}${path}`

// A random suffix keeps a test independent from any other spec creating workspaces
export const randomSuffix = () => Math.random().toString(36).slice(2, 10)
export const randomName = () => `Workspace ${randomSuffix()}`

// One gigabyte, in the bytes the server counts a quota in
export const GB = 1024 ** 3

// The URL of a route of the OCS API of the app: baseUrl ends with /index.php, OCS lives next to it
export const ocsUrl = (path) => Cypress.config('baseUrl').replace(/\/index\.php\/?$/, `/ocs/v2.php/apps/workspace/api/v1${path}`)

// cy.request() as the given user. The OCS-APIRequest header also lets the routes of
// appinfo/routes.php skip their CSRF check. Nextcloud picks the session cookie left by a
// previous request over the Basic auth, so the cookies are cleared first: this also logs
// out the page of a cy.login(), so call it before cy.login(), never after
const request = (user, method, url, body, options) => {
	cy.clearAllCookies()
	return cy.request({
		method,
		url,
		auth: { user: user.userId, pass: user.password },
		headers: { 'OCS-APIRequest': 'true', Accept: 'application/json' },
		body,
		...options,
	})
}

// cy.request() as the given user, without failing on an error status
export const requestAs = (user, method, url, body) => request(user, method, url, body, { failOnStatusCode: false })

// cy.request() as the given user on the OCS API of the app, yields ocs.data
const ocs = (user, method, path, body) => request(user, method, ocsUrl(path), body).its('body.ocs.data')

// Changes the language of the user ("en" or "fr"). cy.modifyUser logs in as the user, which
// the session cookie of an earlier request would otherwise override
export const setLanguage = (user, language) => {
	cy.clearAllCookies()
	cy.modifyUser(user, 'language', language)
}

// A new user, with no role and in no workspace, who reads the app in the given language
export const createUser = (language) => cy.createRandomUser().then((user) => {
	setLanguage(user, language)
	return cy.wrap(user)
})

// A new General Manager who reads the app in the given language
export const createGeneralManager = (language) => createUser(language).then((generalManager) => {
	occ('group:adduser', 'GeneralManager', generalManager.userId)
	return cy.wrap(generalManager)
})

// A new workspace, created through the OCS API since the creation itself is covered by
// creation.cy.js. Yields the workspace, with its id and name
export const createSpace = (generalManager, name = randomName()) => ocs(generalManager, 'POST', '/spaces', { name })

// Changes the name, color or quota of the workspace
export const editSpace = (generalManager, space, changes) => ocs(generalManager, 'PATCH', `/spaces/${space.id}`, changes)

// The workspace as the server saved it, read by a General Manager
export const findSpace = (generalManager, space) => ocs(generalManager, 'GET', `/spaces/${space.id}`)

// The workspaces whose name matches, read by a General Manager. The OCS API answers null
// rather than an empty list when none matches
export const searchSpaces = (generalManager, name) => request(generalManager, 'GET', ocsUrl('/spaces'), undefined, { qs: { name } })
	.its('body.ocs')
	.then(({ data }) => data ?? [])

// Adds the user to the user group of the workspace
export const addSpaceMember = (generalManager, space, user) => ocs(generalManager, 'POST', `/spaces/${space.id}/users`, { uids: [user.userId] })

// Makes the user a Workspace Manager of the workspace
export const addSpaceManager = (generalManager, space, user) => ocs(generalManager, 'POST', `/spaces/${space.id}/workspace-manager`, { uid: user.userId })

// A new user, in the given language, added to the user group of the workspace
export const createSpaceMember = (generalManager, space, language) => createUser(language).then((member) => {
	addSpaceMember(generalManager, space, member)
	return cy.wrap(member)
})

// The name of the subgroup the tests create in a workspace
export const SUBGROUP = 'maison'

// A new subgroup of the workspace, whose display name also carries the workspace name
export const createSubgroup = (generalManager, space, name) => ocs(generalManager, 'POST', `/spaces/${space.id}/groups`, { name })

// A new General Manager, in the given language, with a workspace and a SUBGROUP subgroup in it.
// Yields { generalManager, space }
export const createGeneralManagerWithSpace = (language) => createGeneralManager(language).then((generalManager) => {
	return createSpace(generalManager).then((space) => {
		createSubgroup(generalManager, space, SUBGROUP)
		return cy.wrap({ generalManager, space })
	})
})

// Display names of the manager and user groups of a workspace. An admin can change their
// prefixes, which cypress.config.js reads from the server
export const managerGroupName = (spaceName) => `${Cypress.expose('displayPrefixManagerGroup')}${spaceName}`
export const userGroupName = (spaceName) => `${Cypress.expose('displayPrefixUserGroup')}${spaceName}`

// The three groups of a workspace with a SUBGROUP subgroup, as createGeneralManagerWithSpace() creates:
// display names follow the workspace name, GIDs its id
export const spaceGroups = (spaceId, spaceName) => [
	{ name: managerGroupName(spaceName), gid: `SPACE-GE-${spaceId}` },
	{ name: userGroupName(spaceName), gid: `SPACE-U-${spaceId}` },
	{ name: `G-${SUBGROUP}-${spaceName}`, gid: `SPACE-G-${SUBGROUP}-${spaceId}` },
]

// The navigation of the app, whose label is not translated
export const workspaceNavigation = () => cy.findByRole('navigation', { name: 'workspace navigation' })

// The row of an entry of the navigation, found by its name, with its icon, counter and arrow
export const navigationEntry = (name) => workspaceNavigation()
	.findByRole('link', { name })
	.closest('.app-navigation-entry')

// The color bullet of the entry of a workspace in the navigation
export const navigationBullet = (spaceName) => navigationEntry(spaceName).find('.app-navigation-entry__icon-bullet > div')

// The apps menu of the header and the search of the navigation, in each language the tests run in.
// The apps menu belongs to the server
const navigationLabels = {
	en: {
		openAppsMenu: 'Open apps menu',
		// Testing Library turns the non-breaking space of the label into a space
		searchWorkspaces: 'Search workspaces …', // eslint-disable-line @nextcloud/l10n-non-breaking-space
	},
	fr: {
		openAppsMenu: 'Ouvrir le menu des applications',
		searchWorkspaces: 'Rechercher des workspaces…',
	},
}

// Opens the app from the apps menu of the header, from the Files app
export const openWorkspaceApp = (language) => {
	cy.visit('/apps/files')
	cy.findByRole('button', { name: navigationLabels[language].openAppsMenu }).click()
	cy.findByRole('menuitem', { name: 'Workspace' }).click()
	cy.location('pathname').should('equal', serverPath('/apps/workspace/'))
}

// A General Manager sees the workspaces of every spec: searching from the navigation keeps
// those of a test. Waited for, since the search leaves after a delay and would otherwise
// answer the next test, logged out
export const searchWorkspaces = (language, search) => {
	cy.intercept({ method: 'GET', pathname: '**/apps/workspace/workspaces/count', query: { search } }).as('countSearch')
	cy.intercept({ method: 'GET', pathname: '**/apps/workspace/spaces', query: { search } }).as('spacesSearch')
	workspaceNavigation().findByLabelText(navigationLabels[language].searchWorkspaces).type(search)
	cy.wait(['@countSearch', '@spacesSearch'])
}

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
