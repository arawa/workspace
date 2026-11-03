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

// A hex color, such as that of a workspace, as the browser computes it in CSS
export const rgb = (hex) => `rgb(${hex.match(/[0-9a-f]{2}/gi).map((byte) => parseInt(byte, 16)).join(', ')})`

// One gigabyte, in the bytes the server counts a quota in
export const GB = 1024 ** 3

// The URL of a path of the server outside index.php, such as OCS or WebDAV: baseUrl ends with
// /index.php, they live next to it
const serverUrl = (path) => Cypress.config('baseUrl').replace(/\/index\.php\/?$/, path)

// The URL of a route of the OCS API of the app
export const ocsUrl = (path) => serverUrl(`/ocs/v2.php/apps/workspace/api/v1${path}`)

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

// Saves a file at the root of the files of the user, through WebDAV
export const uploadFile = (user, name, content) => request(user, 'PUT', serverUrl(`/remote.php/dav/files/${user.userId}/${name}`), content)

// Changes a field of the user, such as "email" or "displayname". cy.modifyUser logs in as
// the user, which the session cookie of an earlier request would otherwise override
export const modifyUser = (user, key, value) => {
	cy.clearAllCookies()
	return cy.modifyUser(user, key, value)
}

// Changes the language of the user ("en" or "fr")
export const setLanguage = (user, language) => modifyUser(user, 'language', language)

// Changes the display name of the user, which is their UID until then
export const setDisplayName = (user, displayName) => modifyUser(user, 'displayname', displayName)

// The background of a row of a member, who is no Workspace Manager, in a table of users
export const TRANSPARENT = 'rgba(0, 0, 0, 0)'

// The background of a row of a Workspace Manager in a table of users: the light primary color
// of the theme, as the browser computes it
export const primaryLight = () => cy.document().then((doc) => {
	const probe = doc.createElement('div')
	probe.style.backgroundColor = 'var(--color-primary-light)'
	doc.body.appendChild(probe)
	const color = doc.defaultView.getComputedStyle(probe).backgroundColor
	probe.remove()
	return color
})

// Checks the texts of the column headers of the table, in their order
export const shouldHaveColumns = (table, columns) => cy.get(`${table} th`).should(($columns) => {
	expect([...$columns].map((column) => column.textContent.trim())).to.deep.equal(columns)
})

// A new user, with no role and in no workspace, who reads the app in the given language. A user
// who never logs in needs none
export const createUser = (language) => cy.createRandomUser().then((user) => {
	if (language) {
		setLanguage(user, language)
	}
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

// Adds the user to a Nextcloud group
export const addNextcloudGroupMember = (gid, user) => occ('group:adduser', gid, user.userId)

// A new Nextcloud group with the user in it, which belongs to no workspace
export const createGroup = (gid, user) => {
	occ('group:add', gid)
	addNextcloudGroupMember(gid, user)
}

// Adds an existing Nextcloud group to the workspace, as an added group. The OCS API has no route for it
export const addConnectedGroup = (generalManager, space, gid) => request(generalManager, 'POST', `/apps/workspace/spaces/${space.id}/connected-groups`, { gid })

// A new user, in the given language if any, added to the user group of the workspace
export const createSpaceMember = (generalManager, space, language) => createUser(language).then((member) => {
	addSpaceMember(generalManager, space, member)
	return cy.wrap(member)
})

// A new Workspace Manager of the workspace, in the given language if any
export const createSpaceManager = (generalManager, space, language) => createSpaceMember(generalManager, space, language)
	.then((manager) => {
		addSpaceManager(generalManager, space, manager)
		return cy.wrap(manager)
	})

// The name of the subgroup the tests create in a workspace
export const SUBGROUP = 'maison'

// A new subgroup of the workspace, whose display name also carries the workspace name
export const createSubgroup = (generalManager, space, name) => ocs(generalManager, 'POST', `/spaces/${space.id}/groups`, { name })

// Adds the user to a group of the workspace, such as a subgroup created by createSubgroup()
export const addGroupMember = (generalManager, space, gid, user) => ocs(generalManager, 'POST', `/spaces/${space.id}/groups/${gid}/users`, { uids: [user.userId] })

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
// The display name of a subgroup, which carries the workspace name too
export const subgroupName = (name, spaceName) => `G-${name}-${spaceName}`
// The GID of a subgroup, which carries the id of the workspace
export const subgroupGid = (name, spaceId) => `SPACE-G-${name}-${spaceId}`

// GIDs of the manager and user groups of a workspace, which follow its id
export const managerGroupGid = (spaceId) => `SPACE-GE-${spaceId}`
export const userGroupGid = (spaceId) => `SPACE-U-${spaceId}`

// The group of the Workspace Managers of every workspace
export const WORKSPACES_MANAGERS = 'WorkspacesManagers'

// The three groups of a workspace with a SUBGROUP subgroup, as createGeneralManagerWithSpace() creates:
// display names follow the workspace name, GIDs its id
export const spaceGroups = (spaceId, spaceName) => [
	{ name: managerGroupName(spaceName), gid: managerGroupGid(spaceId) },
	{ name: userGroupName(spaceName), gid: userGroupGid(spaceId) },
	{ name: subgroupName(SUBGROUP, spaceName), gid: subgroupGid(SUBGROUP, spaceId) },
]

// The navigation of the app, whose label is not translated
export const workspaceNavigation = () => cy.findByRole('navigation', { name: 'workspace navigation' })

// Opens the page of a group of the workspace from the navigation, given an entry of spaceGroups()
export const openGroupPage = (space, group) => {
	workspaceNavigation().findByRole('link', { name: group.name }).click()
	cy.location('pathname').should('equal', serverPath(`/apps/workspace/group/${space.id}/${group.gid}`))
}

// Logs in and opens the page of the workspace
export const visitSpacePage = (user, space) => {
	cy.login(user)
	cy.visit(`/apps/workspace/workspace/${space.id}`)
}

// Logs in and opens the page of a group from the workspace page. A link straight to it would not
// do: the app opens the workspace page of a user who sees a single workspace
export const visitGroupPage = (user, space, group) => {
	visitSpacePage(user, space)
	openGroupPage(space, group)
}

// A button of the header of the workspace page, next to its name
export const spaceAction = (name) => cy.get('.space-actions').findByRole('button', { name })

// A button of the header of the page of a group, next to its name
export const groupButton = (name) => cy.get('.group-actions').findByRole('button', { name })

// Checks the name of the page of a group
export const shouldShowGroupTitle = (name) => cy.get('.group-name .titles-for-space')
	.invoke('text')
	.invoke('trim')
	.should('equal', name)

// The entries of an open "+" or "···" menu that also holds a text field: @nextcloud/vue then makes
// it a dialog of buttons rather than a menu of menu items
export const menuButtons = () => cy.get('.action-button:visible')

// Clicks an entry of an open "+" or "···" menu that also holds a text field, see menuButtons()
export const clickMenuButton = (text) => menuButtons().contains(text).click()

// The warning that asks to confirm the removal of a group. The "···" menu that opened it stays
// open behind it and hides it from Testing Library: the warning is found by its class
export const warningModal = () => cy.get('.modal-container')

// The display names in the table of users of a workspace or of a group
export const userNames = () => cy.get('.table-space-detail tbody .user-name')

// The row of a user in the table of users, found by their whole display name
export const userRow = (name) => userNames()
	.filter((index, element) => element.textContent.trim() === name)
	.closest('tr')

// Clicks an action of the menu of a user, found by their display name, and waits for the server
// to apply it
export const clickUserAction = (label, name, action, request) => {
	cy.intercept(request).as('userAction')
	userRow(name).findByRole('button', { name: label.actions }).click()
	cy.findByRole('menuitem', { name: action }).click()
	cy.wait('@userAction').its('response.statusCode').should('equal', 200)
}

// Checks that the table of users no longer lists the user, whose display name is their UID
export const shouldNotListUser = (user) => userNames().should(($names) => {
	expect([...$names].map((name) => name.textContent.trim())).not.to.include(user.userId)
})

// The row of an entry of the navigation, found by its name, with its icon, counter and arrow
export const navigationEntry = (name) => workspaceNavigation()
	.findByRole('link', { name })
	.closest('.app-navigation-entry')

// Checks the user counter of an entry of the navigation, a workspace or one of its groups
export const shouldCountUsers = (name, count) => navigationEntry(name)
	.find('.user-counter')
	.should(($counter) => expect($counter.text().trim()).to.equal(String(count)))

// Checks the user counters of a workspace made by createGeneralManagerWithSpace() and of its
// groups in the navigation: the workspace and its user group count its users
export const shouldCountSpaceUsers = (space, { users, managers, subgroupMembers }) => {
	const [managerGroup, userGroup, subgroup] = spaceGroups(space.id, space.name)
	shouldCountUsers(space.name, users)
	shouldCountUsers(userGroup.name, users)
	shouldCountUsers(managerGroup.name, managers)
	shouldCountUsers(subgroup.name, subgroupMembers)
}

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

// Opens the page of a group in the Accounts page and searches the user among its members, which
// the page loads 25 at a time. The group must have other members: the search only filters
// a list that already shows them. Waits for the server to answer the search
const searchAccountsGroupMembers = (gid, user) => {
	cy.intercept({ method: 'GET', pathname: `**/cloud/groups/${gid}/users/details`, query: { search: user.userId } }).as('memberSearch')
	cy.visit(`/settings/users/${gid}`)
	cy.get('[data-cy-user-row]').should('exist')
	cy.findByLabelText('Search accounts and groups…').type(user.userId)
	cy.wait('@memberSearch')
}

// Checks that the Accounts page lists the user among the members of the group
export const shouldListInAccountsGroup = (gid, user) => {
	searchAccountsGroupMembers(gid, user)
	cy.get('[data-cy-user-row]').should(($rows) => {
		expect([...$rows].map((row) => row.dataset.cyUserRow)).to.deep.equal([user.userId])
	})
}

// Checks that the Accounts page does not list the user among the members of the group. The page
// empties the list while it searches: "No accounts" only shows once the search found nobody
export const shouldNotListInAccountsGroup = (gid, user) => {
	searchAccountsGroupMembers(gid, user)
	cy.contains('No accounts').should('be.visible')
	cy.get('[data-cy-user-row]').should('not.exist')
}

// The row of a folder in the Team folders admin settings
export const teamFolderRow = (name) => cy.findByRole('button', { name: `Rename "${name}"` }).closest('tr')

// The edit modal of a workspace, in each language the tests run in
const editModalLabels = {
	en: { title: 'Edit the workspace', save: 'Save' },
	fr: { title: 'Éditer le workspace', save: 'Enregistrer' },
}

// Named by its title. Queried each time rather than aliased: the color picker and the
// quota select open a second dialog or a listbox outside the modal
export const editModal = (language) => cy.findByRole('dialog', { name: editModalLabels[language].title })

// Opens the edit modal from the pencil in the header of the workspace page
export const openEditModal = (generalManager, space, language) => {
	visitSpacePage(generalManager, space)

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
