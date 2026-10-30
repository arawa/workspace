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

// The actions menu of a user, in the tables of users of a workspace and of its groups

import {
	addConnectedGroup,
	addGroupMember,
	addSpaceManager,
	createGeneralManagerWithSpace,
	createGroup,
	createSpaceMember,
	createUser,
	openGroupPage,
	randomSuffix,
	setLanguage,
	spaceGroups,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The trigger of the menu belongs to @nextcloud/vue
const labels = {
	en: {
		actions: 'Actions',
		viewProfile: 'View profile',
		assignManager: 'Assign as WM',
		removeManager: 'Remove WM rights',
		removeUser: 'Remove user',
		removeFromGroup: 'Remove from group',
	},
	fr: {
		actions: 'Actions',
		viewProfile: 'Voir le profil',
		assignManager: 'Assigner comme WM',
		removeManager: 'Retirer les droits de WM',
		removeUser: 'Retirer l\'utilisateur',
		removeFromGroup: 'Retirer du groupe',
	},
}

// Opens the actions menu of the user and checks its entries, in their order, then closes it
const shouldOfferActions = (label, user, actions) => {
	cy.contains('.table-space-detail tbody tr', user.userId)
		.findByRole('button', { name: label.actions })
		.click()
	cy.findByRole('menu').findAllByRole('menuitem').should(($items) => {
		expect([...$items].map((item) => item.textContent.trim())).to.deep.equal(actions)
	})
	cy.get('body').type('{esc}')
	cy.findByRole('menu').should('not.exist')
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
// The Workspace Manager who logs in, and another one, whose row the first one acts on
let manager
let otherManager
// A member of the user group of the workspace
let member
// A member of an added group only
let guest

// Opens the page of a group from the workspace page. A link straight to it would not do:
// the app opens the workspace page of a user who sees a single workspace
const visitGroupPage = (user, group) => {
	cy.login(user)
	cy.visit(`/apps/workspace/workspace/${space.id}`)
	openGroupPage(space, group)
}

before(() => {
	createGeneralManagerWithSpace('en').then((created) => {
		({ generalManager, space } = created)
		;[managerGroup, userGroup, subgroup] = spaceGroups(space.id, space.name)

		createSpaceMember(generalManager, space, 'en').then((user) => {
			manager = user
			addSpaceManager(generalManager, space, manager)
		})
		createSpaceMember(generalManager, space, 'en').then((user) => {
			otherManager = user
			addSpaceManager(generalManager, space, otherManager)
			addGroupMember(generalManager, space, subgroup.gid, otherManager)
		})
		createSpaceMember(generalManager, space, 'en').then((user) => {
			member = user
			addGroupMember(generalManager, space, subgroup.gid, member)
		})
		createUser('en').then((user) => {
			guest = user
			createGroup(addedGroup.gid, guest)
			addConnectedGroup(generalManager, space, addedGroup.gid)
		})
	})
})

for (const [language, label] of Object.entries(labels)) {
	describe(`User actions (${language})`, () => {
		before(() => {
			setLanguage(generalManager, language)
			setLanguage(manager, language)
		})

		// What a General Manager or a Workspace Manager may do to a member of the workspace,
		// to a Workspace Manager, and to a member of an added group, whom no one can remove
		const memberActions = [label.viewProfile, label.assignManager, label.removeUser]
		const managerActions = [label.viewProfile, label.removeManager, label.removeUser]
		const guestActions = [label.viewProfile, label.assignManager]

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				it('offers the actions of each user on the workspace page', () => {
					cy.login(user())
					cy.visit(`/apps/workspace/workspace/${space.id}`)

					shouldOfferActions(label, member, memberActions)
					shouldOfferActions(label, otherManager, managerActions)
					shouldOfferActions(label, guest, guestActions)
				})

				it('offers the same actions on the page of the user group', () => {
					visitGroupPage(user(), userGroup)

					shouldOfferActions(label, member, memberActions)
					shouldOfferActions(label, otherManager, managerActions)
				})

				it('offers to remove the rights of a Workspace Manager on the page of the manager group', () => {
					visitGroupPage(user(), managerGroup)

					shouldOfferActions(label, otherManager, managerActions)
				})

				it('also offers to remove a user from the group on the page of a subgroup', () => {
					visitGroupPage(user(), subgroup)

					shouldOfferActions(label, member, [...memberActions, label.removeFromGroup])
					shouldOfferActions(label, otherManager, [...managerActions, label.removeFromGroup])
				})

				it('never offers to remove a user on the page of an added group', () => {
					visitGroupPage(user(), addedGroup)

					shouldOfferActions(label, guest, guestActions)
				})
			})
		}
	})
}
