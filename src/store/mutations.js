import Vue from 'vue'
import { getLocale } from '@nextcloud/l10n'

const sortSpaces = (state) => {
	const sortedSpaces = {}
	Object.keys(state.spaces)
		.sort((a, b) => {
			// Some javascript engines don't support localCompare's locales
			// and options arguments.
			// This is especially the case of the mocha test framework
			try {
				return a.localeCompare(b, getLocale(), {
					sensitivity: 'base',
					ignorePunctuation: true,
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
	// Adds space to the spaces list and sort them, case-insensitive, and locale-based
	addSpace(state, space) {
		state.spaces[space.name] = space
		sortSpaces(state)
	},
	addUserToWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.name] = user
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	removeGroupFromSpace(state, { name, group }) {
		const space = state.spaces[name]
		delete space.groups[group]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	removeUserFromWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		delete space.users[user.name]
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
		space.users[user.name] = user
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
		sortSpaces(state)
	},
	UPDATE_COLOR(state, { name, colorCode }) {
		const space = state.spaces[name]
		space.color = colorCode
		Vue.set(state.spaces, name, space)
	}
}
