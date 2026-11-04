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

// A General Manager or a Workspace Manager adds users to a workspace from the modal to add users:
// one by one, from the workspace page and from the page of a subgroup, then from a .csv file
// uploaded from the computer or chosen from Files

import {
	admin,
	clickMenuButton,
	createGeneralManagerWithSpace,
	createSpaceManager,
	groupButton,
	shouldCountSpaceUsers,
	shouldListInAccountsGroup,
	shouldListOnlyInAccountsGroup,
	spaceAction,
	spaceGroups,
	uploadFile,
	userRow,
	visitGroupPage,
	visitSpacePage,
	WORKSPACES_MANAGERS,
} from '../../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The "+" menu is named by @nextcloud/vue, the buttons of the file picker by @nextcloud/dialogs
const labels = {
	en: {
		actions: 'Actions',
		addUsers: 'Add users',
		searchTab: 'Search users',
		importTab: 'Import a .csv file',
		searchUsers: 'Start typing to lookup users',
		noUsers: 'No users selected',
		manager: 'WM',
		unselect: 'remove users from selection',
		notMembers: 'Users highlighted in red are not yet member of this workspace. They will be automatically added.',
		addTheseUsers: 'Add these users',
		// Without its ":", behind a non-breaking space in French, which cy.contains() reads as a space
		csvStructure: 'Your CSV file must follow this structure',
		uploadFiles: 'Upload new files',
		chooseFromFiles: 'Choose from Files',
		choose: (file) => `Choose ${file}`,
	},
	fr: {
		actions: 'Actions',
		addUsers: 'Ajouter des utilisateurs',
		searchTab: 'Rechercher des utilisateurs',
		importTab: 'Importer un fichier .csv',
		searchUsers: 'Commencez à taper pour rechercher des utilisateurs',
		noUsers: 'Aucun utilisateur sélectionné',
		manager: 'WM',
		unselect: 'retirer l\'utilisateur de la sélection',
		notMembers: 'Les utilisateurs surlignés en rouge ne sont pas encore membres de ce workspace. Ils seront ajoutés automatiquement.',
		addTheseUsers: 'Ajouter ces utilisateurs',
		// Without its ":", behind a non-breaking space in French, which cy.contains() reads as a space
		csvStructure: 'Votre fichier CSV doit respecter cette structure',
		uploadFiles: 'Téléverser de nouveaux fichiers',
		chooseFromFiles: 'Choisir depuis Fichiers',
		choose: (file) => `Choisir ${file}`,
	},
}

// The requests that look up users and read a .csv file
const LOOKUP_USERS = { method: 'POST', pathname: '**/apps/workspace/api/autoComplete/**' }
const IMPORT_LOCAL = { method: 'POST', pathname: '**/apps/workspace/space/*/import-users/local' }
const IMPORT_FILES = { method: 'POST', pathname: '**/apps/workspace/space/*/import-users/files' }

// The modal to add users, named by its title
const addUsersModal = (label) => cy.findByRole('dialog', { name: label.addUsers })

// The card of a selected user in the modal, found by their display name, which is their UID
const userCard = (user) => cy.contains('.user-card', user.userId)

// Searches a user in the modal, and selects them among those it offers
const selectUser = (label, user) => {
	cy.intercept(LOOKUP_USERS).as('lookupUsers')
	addUsersModal(label).findByPlaceholderText(label.searchUsers).type(user.userId)
	cy.wait('@lookupUsers')
	cy.contains('[role="option"]', user.userId).click()
}

// Checks the users selected in the modal, and which of them are to be Workspace Managers
const shouldSelect = (label, members, managers) => {
	const managerBox = (user) => userCard(user).findByRole('checkbox', { name: label.manager })
	cy.get('.user-card').should('have.length', members.length + managers.length)
	members.forEach((user) => managerBox(user).should('not.be.checked'))
	managers.forEach((user) => managerBox(user).should('be.checked'))
}

// Adds the selected users, and waits for the modal to close
const addSelectedUsers = (label) => {
	addUsersModal(label).contains('button', label.addTheseUsers).click()
	addUsersModal(label).should('not.exist')
}

// Builds a .csv file of users and their role
const csvOf = (members, managers) => ['user,role', ...members.map((user) => `${user.userId},user`), ...managers.map((user) => `${user.userId},wm`)].join('\n')

// New users, yielded in the order they were created
const createUsers = (count) => {
	const users = []
	for (let i = 0; i < count; i++) {
		cy.createRandomUser().then((user) => users.push(user))
	}
	return cy.wrap(users)
}

// Checks the users the table of users lists, and which of them are Workspace Managers
const shouldListUsers = (members, managers) => {
	members.forEach((user) => userRow(user.userId).should('have.class', 'user-simple'))
	managers.forEach((user) => userRow(user.userId).should('have.class', 'user-admin'))
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Add users (${language})`, () => {
		// The Accounts page of the server is not translated by the app: the tests that only read it
		// run in English, and are pending in the other languages
		const itEnglishOnly = language === 'en' ? it : it.skip

		let generalManager
		let space
		// The groups of the workspace, see spaceGroups()
		let managerGroup
		let userGroup
		let maison
		// The Workspace Manager who logs in
		let manager
		// Users added one by one: Ana as a member, Ben as Workspace Manager, Hal selected then
		// taken back from the selection, Cleo from the page of the subgroup
		let ana
		let ben
		let hal
		let cleo
		// Users of the .csv files: Dan and Fay as members, Eve and Gus as Workspace Managers
		let dan
		let eve
		let fay
		let gus

		// Each role gets its own workspace and users, so the second one does not find the first
		// one's changes
		const setUp = () => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
				;[managerGroup, userGroup, maison] = spaceGroups(space.id, space.name)
				createSpaceManager(generalManager, space, language).then((user) => {
					manager = user
				})
			})
			// In no workspace, and never logged in: their language does not matter
			createUsers(8).then((users) => {
				[ana, ben, hal, cleo, dan, eve, fay, gus] = users
			})
		}

		const shouldCount = (counts) => shouldCountSpaceUsers(space, counts)

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				before(setUp)

				const openSpace = () => visitSpacePage(user(), space)

				// Opens the modal from the "+" of the workspace page
				const openAddUsers = () => {
					spaceAction(label.actions).click()
					clickMenuButton(label.addUsers)
					addUsersModal(label).should('be.visible')
				}

				it('shows the modal to add users, with its search and its import of a .csv file', () => {
					openSpace()
					openAddUsers()

					// Opens on the search, with no user selected
					addUsersModal(label).within(() => {
						cy.findByRole('tab', { name: label.searchTab }).should('have.attr', 'aria-selected', 'true')
						cy.findByPlaceholderText(label.searchUsers).should('be.visible')
						cy.contains(label.noUsers).should('be.visible')
						cy.contains('button', label.addTheseUsers).should('be.visible')

						// The import of a .csv file, whose structure the icon next to its tab tells
						cy.findByRole('tab', { name: label.importTab }).click()
						cy.contains('button', label.uploadFiles).should('be.visible')
						cy.contains('button', label.chooseFromFiles).should('be.visible')
						cy.contains(label.noUsers).should('be.visible')
						cy.contains('button', label.addTheseUsers).should('be.visible')
						cy.get('.information-image').click()
					})
					cy.contains('.popover', label.csvStructure).within(() => {
						for (const line of ['user, role', 'pierre.quiroul,user', 'awang,wm', 'radjiv.velasquez,u', 'christine.dupont@nextcloud.fr,wm', 'jdoe@nextcloud.com,user']) {
							cy.contains(line).should('be.visible')
						}
					})
				})

				it('adds users one by one from the workspace page, one of them as Workspace Manager', () => {
					openSpace()
					shouldCount({ users: 1, managers: 1, subgroupMembers: 0 })
					openAddUsers()

					// Each selected user has a box to make them Workspace Manager, and a trash
					selectUser(label, ana)
					selectUser(label, ben)
					selectUser(label, hal)
					userCard(ben).findByRole('checkbox', { name: label.manager }).click({ force: true })
					userCard(hal).findByRole('button', { name: label.unselect }).click()
					shouldSelect(label, [ana], [ben])

					addSelectedUsers(label)
					shouldListUsers([ana], [ben])
					shouldCount({ users: 3, managers: 2, subgroupMembers: 0 })
				})

				it('adds a user who is not a member yet from the page of a subgroup', () => {
					visitGroupPage(user(), space, maison)
					groupButton(label.addUsers).click()

					// The modal tells the user will join the workspace too
					selectUser(label, cleo)
					addUsersModal(label).contains(label.notMembers).should('be.visible')
					addSelectedUsers(label)

					shouldListUsers([cleo], [])
					shouldCount({ users: 4, managers: 2, subgroupMembers: 1 })
				})

				it('adds the users of a .csv file uploaded from the computer', () => {
					openSpace()
					openAddUsers()
					cy.intercept(IMPORT_LOCAL).as('importUsers')

					cy.findByRole('tab', { name: label.importTab }).click()
					addUsersModal(label).find('input[type="file"]').selectFile({
						contents: Cypress.Buffer.from(csvOf([dan], [eve])),
						fileName: 'users.csv',
						mimeType: 'text/csv',
					}, { force: true })
					cy.wait('@importUsers').its('response.statusCode').should('equal', 200)
					shouldSelect(label, [dan], [eve])

					addSelectedUsers(label)
					shouldListUsers([dan], [eve])
					shouldCount({ users: 6, managers: 3, subgroupMembers: 1 })
				})

				it('adds the users of a .csv file chosen from Files', () => {
					const csv = `users-${language}.csv`
					uploadFile(user(), csv, csvOf([fay], [gus]))
					openSpace()
					openAddUsers()
					cy.intercept(IMPORT_FILES).as('importUsers')

					// The file picker of the server opens on the files of the user
					cy.findByRole('tab', { name: label.importTab }).click()
					cy.contains('button', label.chooseFromFiles).click()
					cy.contains('.file-picker__row', csv).click()
					cy.contains('button', label.choose(csv)).click()
					cy.wait('@importUsers').its('response.statusCode').should('equal', 200)
					shouldSelect(label, [fay], [gus])

					addSelectedUsers(label)
					shouldListUsers([fay], [gus])
					shouldCount({ users: 8, managers: 4, subgroupMembers: 1 })
				})

				// Hal, taken back from the selection, is in none of the groups
				itEnglishOnly('lists the added users in the groups of the workspace on the Accounts page', () => {
					cy.login(admin)

					shouldListOnlyInAccountsGroup(userGroup.gid, [manager, ana, ben, cleo, dan, eve, fay, gus])
					shouldListOnlyInAccountsGroup(managerGroup.gid, [manager, ben, eve, gus])
					shouldListOnlyInAccountsGroup(maison.gid, [cleo])
					for (const added of [ben, eve, gus]) {
						shouldListInAccountsGroup(WORKSPACES_MANAGERS, added)
					}
				})
			})
		}
	})
}
