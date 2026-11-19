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

// A General Manager or a Workspace Manager adds Nextcloud groups to a workspace, from the "+" of
// its page and from the navigation, then removes one of them. groups/add-group.cy.js checks the modal
// opens from the navigation

import {
	addGroupMember,
	addNextcloudGroupMember,
	admin,
	clickMenuButton,
	createGeneralManagerWithSpace,
	createGroup,
	createSpaceManager,
	createSpaceMember,
	createUser,
	groupButton,
	randomSuffix,
	serverPath,
	shouldCountSpaceUsers,
	shouldCountUsers,
	shouldListInAccountsGroup,
	shouldNotListUser,
	spaceAction,
	spaceGroups,
	teamFolderRow,
	userNames,
	visitGroupPage,
	visitSpacePage,
	warningModal,
	workspaceNavigation,
} from '../../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The "+" and "···" menus are named by @nextcloud/vue. The warning is read without its line
// breaks and bold
const labels = {
	en: {
		actions: 'Actions',
		addGroup: 'Add a group',
		searchGroups: 'Start typing text to search for groups',
		noGroup: 'No group selected',
		unselect: 'remove group from selection',
		add: 'Add',
		removeAddedGroup: 'Remove added group',
		cancel: 'Cancel',
		confirm: 'Confirm',
		removeWarning: (group, space) => `Warning, after removal of group ${group}, its users will lose access to the ${space} workspace, with the exception of:`
			+ `- Workspace Managers (WM-${space})`
			+ '- users who are members of Workspace groups (prefixed G-)'
			+ '- users who are members of another Added Group'
			+ `- users manually added from the Workspace ${space}`,
	},
	fr: {
		actions: 'Actions',
		addGroup: 'Ajouter un groupe',
		searchGroups: 'Commencez à taper du texte pour rechercher des groupes',
		noGroup: 'Aucun groupe sélectionné',
		unselect: 'retirer le groupe de la sélection',
		add: 'Ajouter',
		removeAddedGroup: 'Retirer le groupe ajouté',
		cancel: 'Annuler',
		confirm: 'Confirmer',
		// French typography: a non-breaking space before ":"
		removeWarning: (group, space) => `Attention, après la suppression du groupe ${group}, ses utilisateurs perdront l'accès au workspace ${space}, à l'exception des\u00a0: `
			+ `- Workspaces Managers (WM-${space})`
			+ '- utilisateurs qui sont membres d\'un groupe workspace (préfixé G-)'
			+ '- utilisateurs qui sont membres d\'un autre groupe Ajouté'
			+ `- des utilisateurs ajoutés manuellement depuis le workspace ${space}`,
	},
}

// The requests that add a group to a workspace and remove it
const ADD_GROUP = { method: 'POST', pathname: '**/apps/workspace/workspaces/*/connected-groups' }
const REMOVE_GROUP = { method: 'DELETE', pathname: '**/apps/workspace/workspaces/*/connected-groups/*' }

// The modal to add a group, named by its title
const addGroupModal = (label) => cy.findByRole('dialog', { name: label.addGroup })

// Searches the groups in the modal, and selects one among those it offers. An option is found by
// its text: its accessible name also holds the initial of its avatar. The search is typed once the
// modal has focused it: before, the text is lost
const selectGroup = (label, search, group) => {
	addGroupModal(label).findByPlaceholderText(label.searchGroups).should('have.focus').type(search)
	cy.contains('[role="option"]', group.gid).click()
}

// Adds the selected group and waits for the server to add it
const submitGroup = (label) => {
	cy.intercept(ADD_GROUP).as('addGroup')
	addGroupModal(label).contains('button', label.add).click()
	addGroupModal(label).should('not.exist')
	cy.wait('@addGroup').its('response.statusCode').should('equal', 200)
}

// Checks that the table of users lists the users, among others, whose display name is their UID
const shouldListUsers = (users) => userNames().should(($names) => {
	expect([...$names].map((name) => name.textContent.trim())).to.include.members(users.map((user) => user.userId))
})

for (const [language, label] of Object.entries(labels)) {
	describe(`Added groups (${language})`, () => {
		// The pages of the server (Accounts, Team folders) are not translated by the app: the tests
		// that only read them run in English, and are pending in the other languages
		const itEnglishOnly = language === 'en' ? it : it.skip

		let generalManager
		let space
		// The subgroup of the workspace, see spaceGroups()
		let maison
		// The Nextcloud groups added to the workspace: removed, then kept
		let alpha
		let beta
		// The Workspace Manager who logs in
		let manager
		// Members of alpha. Removing alpha takes the workspace from the first only: the others
		// are a Workspace Manager, a member of a subgroup, a member of beta, and a member added
		// to the workspace by hand
		let alphaOnly
		let alphaManager
		let alphaSubgroupMember
		let alphaBetaMember
		let alphaMember
		// A member of beta only
		let betaOnly

		// Each role gets its own workspace and groups, so the second one does not find the first
		// one's changes. The names of the groups share a suffix, which finds both
		let suffix
		const setUp = () => {
			suffix = randomSuffix()
			alpha = { name: `alpha-${suffix}`, gid: `alpha-${suffix}` }
			beta = { name: `beta-${suffix}`, gid: `beta-${suffix}` }

			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
				maison = spaceGroups(space.id, space.name)[2]
				createSpaceManager(generalManager, space, language).then((user) => {
					manager = user
				})
				// The others never log in: they need no language
				createUser().then((user) => {
					alphaOnly = user
					createGroup(alpha.gid, alphaOnly)
				})
				createSpaceManager(generalManager, space).then((user) => {
					alphaManager = user
					addNextcloudGroupMember(alpha.gid, alphaManager)
				})
				createSpaceMember(generalManager, space).then((user) => {
					alphaSubgroupMember = user
					addGroupMember(generalManager, space, maison.gid, alphaSubgroupMember)
					addNextcloudGroupMember(alpha.gid, alphaSubgroupMember)
				})
				createSpaceMember(generalManager, space).then((user) => {
					alphaMember = user
					addNextcloudGroupMember(alpha.gid, alphaMember)
				})
				createUser().then((user) => {
					alphaBetaMember = user
					createGroup(beta.gid, alphaBetaMember)
					addNextcloudGroupMember(alpha.gid, alphaBetaMember)
				})
				createUser().then((user) => {
					betaOnly = user
					addNextcloudGroupMember(beta.gid, betaOnly)
				})
			})
		}

		// The user counters of the navigation, where only the users of the workspace change
		const shouldCount = (users) => shouldCountSpaceUsers(space, { users, managers: 2, subgroupMembers: 1 })

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				before(setUp)

				const openSpace = () => visitSpacePage(user(), space)

				it('adds a group from the "+" of the workspace page', () => {
					openSpace()
					shouldCount(4)

					// The modal opens empty
					spaceAction(label.actions).click()
					clickMenuButton(label.addGroup)
					addGroupModal(label).within(() => {
						cy.findByRole('heading', { name: label.addGroup }).should('be.visible')
						cy.contains(label.noGroup).should('be.visible')
					})

					// Both groups are selected, then beta is taken back from the selection
					selectGroup(label, suffix, alpha)
					selectGroup(label, suffix, beta)
					cy.get('.group-item').should('have.length', 2)
					cy.contains('.group-item', beta.name)
						.findByRole('button', { name: label.unselect })
						.click()
					cy.get('.group-item').should('have.length', 1).and('contain', alpha.name)

					// Only alpha joins the workspace, with its members who were not in it yet
					submitGroup(label)
					workspaceNavigation().findByRole('link', { name: alpha.name }).should('be.visible')
					workspaceNavigation().findByRole('link', { name: beta.name }).should('not.exist')
					shouldCountUsers(alpha.name, 5)
					shouldCount(6)
				})

				it('adds a group from the navigation', () => {
					openSpace()

					workspaceNavigation().findByRole('button', { name: label.addGroup }).click()
					selectGroup(label, beta.name, beta)
					submitGroup(label)

					workspaceNavigation().findByRole('link', { name: beta.name }).should('be.visible')
					shouldCountUsers(beta.name, 2)
					shouldCount(7)
				})

				itEnglishOnly('lists the added groups in the Team folders admin settings', () => {
					cy.login(admin)
					cy.visit('/settings/admin/groupfolders')

					teamFolderRow(space.name).find('td.groups')
						.should('contain', alpha.name)
						.and('contain', beta.name)
				})

				it('removes an added group once confirmed, and only its members who have no other access lose the workspace', () => {
					visitGroupPage(user(), space, alpha)
					cy.intercept(REMOVE_GROUP).as('removeGroup')

					const openRemoveWarning = () => {
						groupButton(label.actions).click()
						clickMenuButton(label.removeAddedGroup)
						warningModal().invoke('text').should('include', label.removeWarning(alpha.name, space.name))
						warningModal().contains('button', label.cancel).should('be.visible')
						warningModal().contains('button', label.confirm).should('be.visible')
					}

					// Cancelled, the warning closes and the group stays
					openRemoveWarning()
					warningModal().contains('button', label.cancel).click()
					warningModal().should('not.exist')
					cy.location('pathname').should('equal', serverPath(`/apps/workspace/group/${space.id}/${alpha.gid}`))

					// Confirmed, the app opens the workspace page, without the group nor its member
					// who had no other access
					openRemoveWarning()
					warningModal().contains('button', label.confirm).click()
					cy.wait('@removeGroup').its('response.statusCode').should('equal', 200)
					cy.location('pathname').should('equal', serverPath(`/apps/workspace/workspace/${space.id}`))
					workspaceNavigation().findByRole('link', { name: alpha.name }).should('not.exist')
					shouldCountUsers(beta.name, 2)
					shouldNotListUser(alphaOnly)

					// Checked once the page is loaded again: until then, the page also takes away the
					// member of beta, and counts one user less, while the server keeps them. To check
					// right away once removeConnectedGroup() of GroupDetails.vue keeps them
					cy.visit(`/apps/workspace/workspace/${space.id}`)
					shouldCountUsers(beta.name, 2)
					shouldCount(6)
					shouldNotListUser(alphaOnly)
					shouldListUsers([alphaManager, alphaSubgroupMember, alphaBetaMember, alphaMember])
				})

				itEnglishOnly('keeps the removed group and its members in Nextcloud, out of the team folder', () => {
					cy.login(admin)

					shouldListInAccountsGroup(alpha.gid, alphaOnly)

					cy.visit('/settings/admin/groupfolders')
					teamFolderRow(space.name).find('td.groups')
						.should('contain', beta.name)
						.and('not.contain', alpha.name)
				})
			})
		}
	})
}
