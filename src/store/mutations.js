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

export default {
	// Adds a group to a space
	addGroupToSpace(state, { name, group }) {
		const space = state.spaces[name]
		space.groups[group] = {
			gid: group,
			displayName: group,
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
}
