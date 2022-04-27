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

import Vue from 'vue'
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
	addGroupToSpace(state, { name, gid }) {
		const space = state.spaces[name]
		space.groups[gid] = {
			gid,
			displayName: gid,
		}
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	// Adds a space to the workspaces list
	addSpace(state, space) {
		state.spaces[space.name] = space
		sortSpaces(state)
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
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	// Adds a user to a workspace
	// TODO: We might need to update the user's groups property too here
	addUserToWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.uid] = user
		Vue.set(state.spaces, name, space)
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
			space.users[key].groups.splice(index, 1)
		})
		// Saves the space back in the store
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	// Removes a user from a group
	// TODO: We might need to update the user's groups property too here
	removeUserFromGroup(state, { name, gid, user }) {
		const space = state.spaces[name]
		const index = space.users[user.uid].groups.indexOf(gid)
		space.users[user.uid].groups.splice(index, 1)
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	// Removes a user from a workspace
	// TODO: We might need to update the user's groups property too here
	removeUserFromWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		delete space.users[user.uid]
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	// Renames a group
	renameGroup(state, { name, gid, newGroupName }) {
		const space = state.spaces[name]
		space.groups[gid] = {
			gid,
			displayName: newGroupName,
		}
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	setSpaceQuota(state, { name, quota }) {
		const space = state.spaces[name]
		space.quota = quota
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	deleteSpace(state, { space }) {
		delete state.spaces[space.name]
	},
	updateSpace(state, space) {
		delete state.spaces[space.name]
		Vue.set(state.spaces, space.name, space)
		sortSpaces(state)
	},
	updateUser(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.uid] = user
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	UPDATE_COLOR(state, { name, colorCode }) {
		const space = state.spaces[name]
		space.color = colorCode
		Vue.set(state.spaces, name, space)
	},
	EMPTY_GROUPFOLDERS(state) {
		state.groupfolders = {}
	},
	UPDATE_GROUPFOLDERS(state, { groupfolder }) {
		Vue.set(state.groupfolders, groupfolder.mount_point, groupfolder)
		sortGroupfolders(state)
	},
}
