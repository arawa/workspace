/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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
 *
 */

import { set as VueSet } from 'vue'
import { getLocale } from '@nextcloud/l10n'

// Function to sort spaces case-insensitively, and locale-based
// It must be called every time a space is modified to keep the
// spaces list sorted in the left navigation panel of the app
const sortSpaces = (state) => {
	const sortedSpaces = {}
	Object.keys(state.spaces)
		.sort((a, b) => {
			// Some javascript engines don't support localCompare's locales
			// and options arguments.
			// This is especially the case of the mocha test framework
			try {
				return a.localeCompare(b, getLocale(), {
					numeric: true,
					sensitivity: 'base',
				})
			} catch (e) {
				return a.localeCompare(b)
			}
		})
		.forEach((value, index) => {
			sortedSpaces[value] = state.spaces[value]
		})
	state.spaces = sortedSpaces
}

const updateGroupUserCount = (groups, users) => {
	Object.keys(groups).forEach(gid => {
		groups[gid].usersCount = 0
		Object.keys(users).forEach(uid => {
			if (users[uid].groups.includes(gid)) {
				groups[gid].usersCount++
			}
		})
	})
}

// Function to sort groupfolders case-insensitively, and locale-based
// It must be called every time a space is modified to keep the
// groupfolders list sorted in the left navigation panel of the app
const sortGroupfolders = (state) => {
	const sortedGroupfolders = {}
	const groupfolers = []
	for (const mountPoint in state.groupfolders) {
		groupfolers.push(state.groupfolders[mountPoint])
	}
	groupfolers
		.sort((groupfolderCurrent, groupfolderNext) => {
			// Some javascript engines don't support localCompare's locales
			// and options arguments.
			// This is especially the case of the mocha test framework
			try {
				return groupfolderCurrent.mount_point.localeCompare(groupfolderNext.mount_point, getLocale(), {
					numeric: true,
					sensitivity: 'base',
				})
			} catch (e) {
				return groupfolderCurrent.mount_point.localeCompare(groupfolderNext.mount_point)
			}
		})
		.forEach(groupfolder => {
			sortedGroupfolders[groupfolder.mount_point] = state.groupfolders[groupfolder.mount_point]
		})
	state.groupfolders = sortedGroupfolders
}

export default {
	// Adds a group to a space
	addGroupToSpace(state, { space, gid, displayName = undefined, slug }) {
		if (displayName === undefined) {
			displayName = gid
		}
		space.groups[gid] = {
			gid,
			displayName,
			usersCount: 0,
			slug,
		}
		VueSet(state.spaces, space.name, space)
		sortSpaces(state)
	},
	// Adds a space to the workspaces list
	addSpace(state, space) {
		state.spaces[space.name] = space
		sortSpaces(state)
	},
	addSpaces(state, spaces) {
		state.spaces = spaces.spaces
		sortSpaces(state)
	},
	addSpaceAdminUsers(state, space) {
		state.spaces[space.name].managers = space.managers
	},
	UPDATE_USERS(state, { space, users }) {
		space.users = users
		if (space.userCount !== Object.keys(users).length) {
			space.userCount = Object.keys(users).length
			updateGroupUserCount(space.groups, users)
		}
		VueSet(state.spaces, space.name, space)
	},
	SET_LOADING_USERS_WAITING(state, { activated }) {
		state.loadingUsersWaiting = activated
	},
	SET_NO_USERS(state, { activated }) {
		state.noUsers = activated
	},
	INCREMENT_GROUP_USER_COUNT(state, { spaceName, gid }) {
		const space = state.spaces[spaceName]
		space.groups[gid].usersCount++
		VueSet(state.spaces, spaceName, space)
	},
	INCREMENT_ADDED_GROUP_USER_COUNT(state, { spaceName, gid }) {
		const space = state.spaces[spaceName]
		space.added_groups[gid].usersCount++
		VueSet(state.spaces, spaceName, space)
	},
	INCREMENT_SPACE_USER_COUNT(state, { spaceName }) {
		const space = state.spaces[spaceName]
		space.userCount++
		VueSet(state.spaces, spaceName, space)
	},
	SET_COUNT_WORKSPACES(state, { count }) {
		state.countWorkspaces = count
	},
	INCREMENT_COUNT_WORKSPACES(state) {
		state.countWorkspaces++
	},
	DECREMENT_COUNT_WORKSPACES(state) {
		if (state.countWorkspaces === 0) {
			return
		}
		state.countWorkspaces--
	},
	DECREMENT_GROUP_USER_COUNT(state, { spaceName, gid }) {
		const space = state.spaces[spaceName]
		space.groups[gid].usersCount--
		VueSet(state.spaces, spaceName, space)
	},
	DECREMENT_SPACE_USER_COUNT(state, { spaceName }) {
		const space = state.spaces[spaceName]
		space.userCount--
		VueSet(state.spaces, spaceName, space)
	},
	SUBSTRACTION_SPACE_USER_COUNT(state, { spaceName, usersCount }) {
		const space = state.spaces[spaceName]
		space.userCount -= usersCount
		VueSet(state.spaces, spaceName, space)
	},
	SUBSTRACTION_GROUP_USER_COUNT(state, { spaceName, gid, usersCount }) {
		const space = state.spaces[spaceName]
		space.groups[gid].usersCount -= usersCount
		VueSet(state.spaces, spaceName, space)
	},
	CHANGE_USER_ROLE(state, { spaceName, user, role }) {
		const space = state.spaces[spaceName]
		space.users[user.uid].role = role
		VueSet(state.spaces, spaceName, space)
	},
	REMOVE_USER_MANAGER(state, { spaceName, user }) {
		const space = state.spaces[spaceName]
		delete space.managers[user.uid]
		VueSet(state.spaces, spaceName, space)
	},
	ADD_USER_MANAGER(state, { spaceName, user }) {
		const space = state.spaces[spaceName]

		if (space.managers === null) {
			space.managers = {}
		}

		space.managers[user.uid] = user
		VueSet(state.spaces, spaceName, space)
	},
	// Adds a user to a group
	addUserToGroup(state, { name, gid, user }) {
		const space = state.spaces[name]
		if (space.users[user.uid] !== undefined) {
			if (!space.users[user.uid].groups.includes(gid)) {
				space.users[user.uid].groups.push(gid)
			}
		} else {
			user.groups.push(gid)
			space.users[user.uid] = user
		}
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	// Adds a user to a workspace
	// TODO: We might need to update the user's groups property too here
	addUserToWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.uid] = user
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	addConnectedGroupToWorkspace(state, { space, group, slug }) {
		space.added_groups[group.gid] = { displayName: group.displayName, gid: group.gid, slug, usersCount: 0 }
		VueSet(state.spaces, space.name, space)
		sortSpaces(state)
	},
	// Removes a group from a space
	removeGroupFromSpace(state, { name, gid }) {
		const space = state.spaces[name]
		// Deletes the group from the space's groups attribute
		delete space.groups[gid]
		// Removes also the group from all users' groups attribute
		Object.keys(space.users).forEach(key => {
			const index = space.users[key].groups.indexOf(gid)
			if (index >= 0) {
				space.users[key].groups.splice(index, 1)
			}
		})
		// Saves the space back in the store
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	removeAddedGroupFromSpace(state, { name, gid }) {
		const space = state.spaces[name]
		// Deletes the group from the space's groups attribute
		delete space.added_groups[gid]
		// Saves the space back in the store
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	// Removes a user from a group
	// TODO: We might need to update the user's groups property too here
	removeUserFromGroup(state, { name, gid, user }) {
		const space = state.spaces[name]
		const index = space.users[user.uid].groups.indexOf(gid)
		space.users[user.uid].groups.splice(index, 1)
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	// Removes a user from a workspace
	// TODO: We might need to update the user's groups property too here
	removeUserFromWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		delete space.users[user.uid]
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	// Renames a group
	renameGroup(state, { name, group }) {
		const space = state.spaces[name]
		space.groups[group.gid] = {
			gid: group.gid,
			displayName: group.displayName,
			slug: group.slug,
			types: group.types,
			usersCount: group.usersCount,
		}
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	setSpaceQuota(state, { name, quota }) {
		const space = state.spaces[name]
		space.quota = quota
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	deleteSpace(state, { space }) {
		delete state.spaces[space.name]
	},
	updateSpace(state, space) {
		delete state.spaces[space.name]
		VueSet(state.spaces, space.name, space)
		sortSpaces(state)
	},
	updateUser(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.uid] = user
		delete state.spaces[space.name]
		VueSet(state.spaces, name, space)
		sortSpaces(state)
	},
	UPDATE_COLOR(state, { name, colorCode }) {
		const space = state.spaces[name]
		space.color = colorCode
		VueSet(state.spaces, name, space)
	},
	EMPTY_GROUPFOLDERS(state) {
		state.groupfolders = {}
	},
	UPDATE_GROUPFOLDERS(state, { groupfolder }) {
		VueSet(state.groupfolders, groupfolder.mount_point, groupfolder)
		sortGroupfolders(state)
	},
	TOGGLE_USER_CONNECTED(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.uid].is_connected = !space.users[user.uid].is_connected
		VueSet(state.spaces, name, space)
	},
}
