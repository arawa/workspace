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

// A General Manager or a Workspace Manager removes a user from a workspace, from its page and from
// the page of its user group, removes the rights of a Workspace Manager from the page of the manager
// group, and removes a user from a subgroup from the page of the subgroup

import {
	addGroupMember,
	addSpaceManager,
	admin,
	clickUserAction,
	createGeneralManagerWithSpace,
	createSpace,
	createSpaceMember,
	managerGroupGid,
	shouldCountSpaceUsers,
	shouldListInAccountsGroup,
	shouldListOnlyInAccountsGroup,
	shouldNotListInAccountsGroup,
	shouldNotListUser,
	spaceGroups,
	visitGroupPage,
	visitSpacePage,
	WORKSPACES_MANAGERS,
} from '../../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The trigger of the menu belongs to @nextcloud/vue
const labels = {
	en: {
		actions: 'Actions',
		removeManager: 'Remove WM rights',
		removeUser: 'Remove user',
		removeFromGroup: 'Remove from group',
	},
	fr: {
		actions: 'Actions',
		removeManager: 'Retirer les droits de WM',
		removeUser: 'Retirer l\'utilisateur',
		removeFromGroup: 'Retirer du groupe',
	},
}

// The requests each action sends
const REMOVE_USER = { method: 'PATCH', pathname: '**/apps/workspace/spaces/*/users/*/groups' }
const REMOVE_MANAGER = { method: 'PATCH', pathname: '**/apps/workspace/api/space/*/user/*' }
const REMOVE_FROM_GROUP = { method: 'PATCH', pathname: '**/apps/workspace/api/group/delUser/*' }

// Clicks an action of the menu of the user, whose display name is their UID
const clickAction = (label, user, action, request) => clickUserAction(label, user.userId, action, request)

for (const [language, label] of Object.entries(labels)) {
	describe(`User removal (${language})`, () => {
		// The Accounts page of the server is not translated by the app: the tests that only read it
		// run in English, and are pending in the other languages
		const itEnglishOnly = language === 'en' ? it : it.skip

		let generalManager
		let space
		let otherSpace
		// The groups of the workspace, see spaceGroups()
		let managerGroup
		let userGroup
		let subgroup
		// The Workspace Manager who logs in, in every group of the workspace, so none gets empty
		let manager
		// A member, in the subgroup, removed from the workspace
		let ana
		// A Workspace Manager of this workspace only, in the subgroup, removed from the workspace
		let ben
		// A Workspace Manager of this workspace and of another one, removed from this one
		let eve
		// Workspace Managers in the subgroup: Cleo loses her rights from the page of the manager
		// group, Dan is removed from the page of the user group, Fay from the page of the subgroup
		let cleo
		let dan
		let fay

		// A new member of the workspace, made Workspace Manager and added to the subgroup if asked.
		// Only a member who logs in needs the language
		const createMember = ({ isManager = false, inSubgroup = false, logsIn = false } = {}) => createSpaceMember(generalManager, space, logsIn ? language : undefined)
			.then((user) => {
				if (isManager) {
					addSpaceManager(generalManager, space, user)
				}
				if (inSubgroup) {
					addGroupMember(generalManager, space, subgroup.gid, user)
				}
				return cy.wrap(user)
			})

		// Each role gets its own workspaces, so the second one does not find the first one's changes
		const setUp = () => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
				;[managerGroup, userGroup, subgroup] = spaceGroups(space.id, space.name)
				createSpace(generalManager).then((created) => {
					otherSpace = created
				})
				createMember({ isManager: true, inSubgroup: true, logsIn: true }).then((user) => {
					manager = user
				})
				createMember({ inSubgroup: true }).then((user) => {
					ana = user
				})
				createMember({ isManager: true, inSubgroup: true }).then((user) => {
					ben = user
				})
				createMember({ isManager: true }).then((user) => {
					eve = user
					addSpaceManager(generalManager, otherSpace, eve)
				})
				createMember({ isManager: true, inSubgroup: true }).then((user) => {
					cleo = user
				})
				createMember({ isManager: true, inSubgroup: true }).then((user) => {
					dan = user
				})
				createMember({ isManager: true, inSubgroup: true }).then((user) => {
					fay = user
				})
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
				const openGroup = (group) => visitGroupPage(user(), space, group)

				it('removes a member from the workspace page', () => {
					openSpace()
					shouldCount({ users: 7, managers: 6, subgroupMembers: 6 })
					clickAction(label, ana, label.removeUser, REMOVE_USER)

					shouldNotListUser(ana)
					shouldCount({ users: 6, managers: 6, subgroupMembers: 5 })
				})

				it('removes a Workspace Manager from the workspace page', () => {
					openSpace()
					clickAction(label, ben, label.removeUser, REMOVE_USER)

					shouldNotListUser(ben)
					shouldCount({ users: 5, managers: 5, subgroupMembers: 4 })
				})

				it('removes a Workspace Manager of another workspace from the workspace page', () => {
					openSpace()
					clickAction(label, eve, label.removeUser, REMOVE_USER)

					shouldNotListUser(eve)
					shouldCount({ users: 4, managers: 4, subgroupMembers: 4 })
				})

				it('removes the rights of a Workspace Manager from the page of the manager group', () => {
					openGroup(managerGroup)
					clickAction(label, cleo, label.removeManager, REMOVE_MANAGER)

					shouldNotListUser(cleo)
					shouldCount({ users: 4, managers: 3, subgroupMembers: 4 })
				})

				it('removes a Workspace Manager from the workspace from the page of the user group', () => {
					openGroup(userGroup)
					clickAction(label, dan, label.removeUser, REMOVE_USER)

					shouldNotListUser(dan)
					shouldCount({ users: 3, managers: 2, subgroupMembers: 3 })
				})

				it('removes a Workspace Manager from the page of the subgroup', () => {
					openGroup(subgroup)
					clickAction(label, fay, label.removeFromGroup, REMOVE_FROM_GROUP)

					shouldNotListUser(fay)
					shouldCount({ users: 3, managers: 2, subgroupMembers: 2 })
				})

				// Checked once, after all the removals: each group of the workspace keeps only the
				// members left in it
				itEnglishOnly('keeps in each group of the workspace only the members left in it', () => {
					cy.login(admin)

					shouldListOnlyInAccountsGroup(userGroup.gid, [manager, cleo, fay])
					shouldListOnlyInAccountsGroup(managerGroup.gid, [manager, fay])
					shouldListOnlyInAccountsGroup(subgroup.gid, [manager, cleo])
				})

				itEnglishOnly('keeps in WorkspacesManagers only the users who still manage a workspace', () => {
					cy.login(admin)

					shouldListInAccountsGroup(managerGroupGid(otherSpace.id), eve)
					for (const removed of [ben, cleo, dan]) {
						shouldNotListInAccountsGroup(WORKSPACES_MANAGERS, removed)
					}
					for (const kept of [eve, fay]) {
						shouldListInAccountsGroup(WORKSPACES_MANAGERS, kept)
					}
				})
			})
		}
	})
}
