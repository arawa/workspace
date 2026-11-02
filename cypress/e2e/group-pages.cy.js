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

// The pages of the groups of a workspace: its manager group, its user group, a subgroup and an
// added group, as a General Manager or a Workspace Manager sees them

import {
	addConnectedGroup,
	addGroupMember,
	addNextcloudGroupMember,
	createGeneralManagerWithSpace,
	createGroup,
	createSpaceManager,
	createSpaceMember,
	createUser,
	groupButton,
	menuButtons,
	primaryLight,
	randomSuffix,
	setLanguage,
	shouldHaveColumns,
	shouldShowGroupTitle,
	spaceGroups,
	TRANSPARENT,
	userNames,
	userRow,
	visitGroupPage,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The "···" menu and the actions menu of a user are named by @nextcloud/vue
const labels = {
	en: {
		actions: 'Actions',
		viewProfile: 'View profile',
		addUsers: 'Add users',
		renameGroup: 'Rename group',
		deleteGroup: 'Delete group',
		removeAddedGroup: 'Remove added group',
		columns: ['', 'Users', 'Role', 'Groups', ''],
	},
	fr: {
		actions: 'Actions',
		viewProfile: 'Voir le profil',
		addUsers: 'Ajouter des utilisateurs',
		renameGroup: 'Renommer le groupe',
		deleteGroup: 'Supprimer le groupe',
		removeAddedGroup: 'Retirer le groupe ajouté',
		columns: ['', 'Utilisateurs', 'Rôle', 'Groupes', ''],
	},
}

// A group that belongs to no workspace, added to the workspace
const addedGid = `added-${randomSuffix()}`
const addedGroup = { name: addedGid, gid: addedGid }

// Shared by every test: each language only changes that of the users who log in
let generalManager
let space
// The groups of the workspace, see spaceGroups()
let managerGroup
let userGroup
let subgroup
// The Workspace Manager who logs in, and another one, also in the added group
let manager
let otherManager
// A member of the workspace, and a member of the added group only
let member
let guest

before(() => {
	createGeneralManagerWithSpace('en').then((created) => {
		({ generalManager, space } = created)
		;[managerGroup, userGroup, subgroup] = spaceGroups(space.id, space.name)

		// Only the Workspace Manager who logs in needs a language
		createSpaceManager(generalManager, space, 'en').then((user) => {
			manager = user
			addGroupMember(generalManager, space, subgroup.gid, manager)
		})
		createSpaceMember(generalManager, space).then((user) => {
			member = user
			addGroupMember(generalManager, space, subgroup.gid, member)
		})
		createUser().then((user) => {
			guest = user
			createGroup(addedGroup.gid, guest)
		})
		createSpaceManager(generalManager, space).then((user) => {
			otherManager = user
			addGroupMember(generalManager, space, subgroup.gid, otherManager)
			addNextcloudGroupMember(addedGroup.gid, otherManager)
			addConnectedGroup(generalManager, space, addedGroup.gid)
		})
	})
})

// Checks the users the table lists, whatever their order, which workspace-page.cy.js checks:
// the row of a Workspace Manager has the light primary color, and each row has an actions menu.
// The row of the user who logs in may only offer to view their profile: @nextcloud/vue then
// shows that single action as a button, without a menu
const shouldListUsers = (label, self, managers, members) => {
	userNames().should(($names) => {
		expect([...$names].map((name) => name.textContent.trim()))
			.to.have.members([...managers, ...members].map((user) => user.userId))
	})
	primaryLight().then((primary) => {
		const rows = [...managers.map((user) => [user, primary]), ...members.map((user) => [user, TRANSPARENT])]
		for (const [user, background] of rows) {
			userRow(user.userId)
				.should('have.css', 'background-color', background)
				.find('.user-actions button')
				.should('be.visible')
				.and('have.attr', 'aria-label', user === self ? label.viewProfile : label.actions)
		}
	})
}

// Opens the "···" menu of the group and checks its entries, in their order, then closes it. The
// menu also holds the hidden input of the new name
const shouldOfferGroupActions = (label, actions) => {
	groupButton(label.actions).click()
	menuButtons().should(($buttons) => {
		expect([...$buttons].map((button) => button.textContent.trim())).to.deep.equal(actions)
	})
	groupButton(label.actions).click()
	menuButtons().should('not.exist')
}

// Clicks the "+" of the group, which opens the modal to add users
const shouldOpenAddUsers = (label) => {
	groupButton(label.addUsers).click()
	cy.findByRole('dialog', { name: label.addUsers }).should('be.visible')
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Group pages (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
			setLanguage(manager, language)
		})

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				// Opens the page of a group, and checks its header, its icon and name, and the columns
				// of its table
				const openGroup = (group) => {
					visitGroupPage(user(), space, group)
					cy.get('.group-name svg').should('be.visible')
					shouldShowGroupTitle(group.name)
					shouldHaveColumns('.table-space-detail', label.columns)
				}

				it('shows the page of the manager group', () => {
					openGroup(managerGroup)

					// Its users can only be added: neither renamed nor deleted, the group has no "···"
					groupButton(label.actions).should('not.exist')
					shouldListUsers(label, user(), [manager, otherManager], [])
					shouldOpenAddUsers(label)
				})

				it('shows the page of the user group', () => {
					openGroup(userGroup)

					// The members of an added group are members of the workspace too
					groupButton(label.actions).should('not.exist')
					shouldListUsers(label, user(), [manager, otherManager], [member, guest])
					shouldOpenAddUsers(label)
				})

				it('shows the page of a subgroup', () => {
					openGroup(subgroup)

					shouldOfferGroupActions(label, [label.renameGroup, label.deleteGroup])
					// The user who logs in may also be removed from a subgroup: their row has a menu
					shouldListUsers(label, null, [manager, otherManager], [member])
					shouldOpenAddUsers(label)
				})

				it('shows the page of an added group', () => {
					openGroup(addedGroup)

					// Its members are managed outside the workspace: no "+" to add some
					groupButton(label.addUsers).should('not.exist')
					shouldOfferGroupActions(label, [label.removeAddedGroup])
					shouldListUsers(label, null, [otherManager], [guest])
				})
			})
		}
	})
}
