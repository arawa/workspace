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

// A General Manager or a Workspace Manager creates subgroups of a workspace, from the "+" of its
// page and from the navigation, renames one and deletes the other

import {
	addGroupMember,
	admin,
	clickMenuButton,
	createGeneralManagerWithSpace,
	createSpaceManager,
	createSpaceMember,
	groupButton,
	openGroupPage,
	searchAccountsGroups,
	serverPath,
	shouldCountSpaceUsers,
	shouldCountUsers,
	shouldShowGroupTitle,
	spaceAction,
	spaceGroups,
	subgroupGid,
	subgroupName,
	teamFolderRow,
	visitGroupPage,
	visitSpacePage,
	warningModal,
	workspaceNavigation,
} from '../support/helpers.js'

// What a General Manager or a Workspace Manager reads, in each language the tests run in.
// The "+" and "···" menus are named by @nextcloud/vue
const labels = {
	en: {
		actions: 'Actions',
		workspaceGroups: 'Workspace groups',
		createGroup: 'Create a workspace group',
		groupName: 'Group name',
		renameGroup: 'Rename group',
		deleteGroup: 'Delete group',
		cancel: 'Cancel',
		confirm: 'Confirm',
		deleteWarning: (group, space) => `Please note that after deleting the ${group} group, its users will retain access to the ${space} workspace`,
	},
	fr: {
		actions: 'Actions',
		workspaceGroups: 'Groupes workspace',
		createGroup: 'Créer un groupe workspace',
		groupName: 'Nom du groupe',
		renameGroup: 'Renommer le groupe',
		deleteGroup: 'Supprimer le groupe',
		cancel: 'Annuler',
		confirm: 'Confirmer',
		deleteWarning: (group, space) => `Veuillez noter qu'après la suppression du groupe ${group}, ses utilisateurs conserveront l'accès au workspace ${space}`,
	},
}

// The requests that create, rename and delete a subgroup
const CREATE_GROUP = { method: 'POST', pathname: '**/apps/workspace/api/group' }
const RENAME_GROUP = { method: 'PATCH', pathname: '**/apps/workspace/api/group/*' }
const DELETE_GROUP = { method: 'DELETE', pathname: '**/apps/workspace/api/group/*' }

// The names of the subgroups the tests create, then the new name of the second one, with an
// accent and a slash
const CREATED_FROM_PAGE = 'projet'
const CREATED_FROM_NAVIGATION = 'atelier'
const NEW_NAME = 'Réunion/équipe'

for (const [language, label] of Object.entries(labels)) {
	describe(`Subgroups (${language})`, () => {
		let generalManager
		let space
		// The user group of the workspace, see spaceGroups()
		let userGroup
		// The Workspace Manager who logs in, and a member, added to the subgroup deleted
		let manager
		let member

		// Each role gets its own workspace, so the second one does not find the first one's groups
		const setUp = () => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
				userGroup = spaceGroups(space.id, space.name)[1]
				createSpaceManager(generalManager, space, language).then((user) => {
					manager = user
				})
				// Never logs in: needs no language
				createSpaceMember(generalManager, space).then((user) => {
					member = user
				})
			})
		}

		// The subgroups the tests create, with the GID the app gives them
		const createdGroup = (name) => ({ name: subgroupName(name, space.name), gid: subgroupGid(name, space.id) })

		// Checks that the app opened the page of the new subgroup, which the navigation lists
		// with no users
		const shouldOpenNewGroup = (name) => {
			const group = createdGroup(name)
			cy.wait('@createGroup').its('response.statusCode').should('equal', 200)
			cy.location('pathname').should('equal', serverPath(`/apps/workspace/group/${space.id}/${group.gid}`))
			shouldShowGroupTitle(group.name)
			shouldCountUsers(group.name, 0)
		}

		const roles = {
			'a General Manager': () => generalManager,
			'a Workspace Manager': () => manager,
		}

		for (const [role, user] of Object.entries(roles)) {
			describe(`as ${role}`, () => {
				before(setUp)

				const openSpace = () => visitSpacePage(user(), space)

				it('creates a subgroup from the "+" of the workspace page', () => {
					openSpace()
					cy.intercept(CREATE_GROUP).as('createGroup')

					spaceAction(label.actions).click()
					clickMenuButton(label.createGroup)
					cy.findByPlaceholderText(label.groupName).type(`${CREATED_FROM_PAGE}{enter}`)

					shouldOpenNewGroup(CREATED_FROM_PAGE)
				})

				it('creates a subgroup from the navigation', () => {
					openSpace()
					cy.intercept(CREATE_GROUP).as('createGroup')

					workspaceNavigation()
						.contains('.app-navigation-caption', label.workspaceGroups)
						.findByRole('button')
						.click()
					cy.get('.ws-modal-action input').type(`${CREATED_FROM_NAVIGATION}{enter}`)

					shouldOpenNewGroup(CREATED_FROM_NAVIGATION)
				})

				it('renames a subgroup with an accent and a slash', () => {
					const group = createdGroup(CREATED_FROM_NAVIGATION)
					const renamed = { ...group, name: createdGroup(NEW_NAME).name }
					visitGroupPage(user(), space, group)
					cy.intercept(RENAME_GROUP).as('renameGroup')

					// The field of the new name opens empty
					groupButton(label.actions).click()
					clickMenuButton(label.renameGroup)
					cy.findByPlaceholderText(label.groupName).should('have.value', '').type(`${NEW_NAME}{enter}`)
					cy.wait('@renameGroup').its('response.statusCode').should('equal', 200)

					// Opened again from the workspace page, so the navigation and the page show the name
					// the server saved. A reload would not do: the app opens the workspace page of a
					// user who sees a single workspace
					cy.visit(`/apps/workspace/workspace/${space.id}`)
					workspaceNavigation().findByRole('link', { name: group.name }).should('not.exist')
					openGroupPage(space, renamed)
					shouldShowGroupTitle(renamed.name)
				})

				it('lists the renamed subgroup on the Accounts page and in the Team folders admin settings', () => {
					const renamed = createdGroup(NEW_NAME).name
					cy.login(admin)

					cy.visit('/settings/users')
					searchAccountsGroups(NEW_NAME)
					cy.findByRole('link', { name: renamed })
						.should('be.visible')
						.and('have.prop', 'pathname', serverPath(`/settings/users/${createdGroup(CREATED_FROM_NAVIGATION).gid}`))

					cy.visit('/settings/admin/groupfolders')
					teamFolderRow(space.name).find('td.groups')
						.should('contain', renamed)
						.and('not.contain', createdGroup(CREATED_FROM_NAVIGATION).name)
				})

				it('deletes a subgroup once confirmed, and its users keep the workspace', () => {
					const group = createdGroup(CREATED_FROM_PAGE)
					addGroupMember(generalManager, space, group.gid, member)
					visitGroupPage(user(), space, group)
					shouldCountUsers(group.name, 1)
					cy.intercept(DELETE_GROUP).as('deleteGroup')

					const openDeleteWarning = () => {
						groupButton(label.actions).click()
						clickMenuButton(label.deleteGroup)
						warningModal().within(() => {
							cy.contains(label.deleteWarning(group.name, space.name)).should('be.visible')
							cy.contains('button', label.cancel).should('be.visible')
							cy.contains('button', label.confirm).should('be.visible')
						})
					}

					// Cancelled, the warning closes and the subgroup stays
					openDeleteWarning()
					warningModal().contains('button', label.cancel).click()
					warningModal().should('not.exist')
					shouldShowGroupTitle(group.name)

					// Confirmed, the app opens the workspace page, without the subgroup
					openDeleteWarning()
					warningModal().contains('button', label.confirm).click()
					cy.wait('@deleteGroup').its('response.statusCode').should('equal', 200)
					cy.location('pathname').should('equal', serverPath(`/apps/workspace/workspace/${space.id}`))
					workspaceNavigation().findByRole('link', { name: group.name }).should('not.exist')

					// No user left the workspace nor its other groups
					shouldCountSpaceUsers(space, { users: 2, managers: 1, subgroupMembers: 0 })
				})

				it('removes the deleted subgroup from the Accounts page and from the Team folders admin settings', () => {
					const deleted = createdGroup(CREATED_FROM_PAGE).name
					cy.login(admin)

					cy.visit('/settings/users')
					searchAccountsGroups(deleted).should('be.empty')

					cy.visit('/settings/admin/groupfolders')
					teamFolderRow(space.name).find('td.groups')
						.should('contain', userGroup.name)
						.and('not.contain', deleted)
				})
			})
		}
	})
}
