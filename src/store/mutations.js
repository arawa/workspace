import Vue from 'vue'
import { getLocale } from '@nextcloud/l10n'

export default {
	// Adds a group to a space
	addGroupToSpace(state, { name, group }) {
		const space = state.spaces[name]
		space.groups[group] = group
		Vue.set(state.spaces, name, space)
	},
	// Adds space to the spaces list and sort them, case-insensitive, and locale-based
	addSpace(state, space) {
		state.spaces[space.name] = space
		const sortedSpaces = {}
		Object.keys(state.spaces)
			.sort((a, b) => a.localeCompare(b, getLocale(), {
				sensitivity: 'base',
				ignorePunctuation: true,
			}))
			.forEach((value, index) => {
				sortedSpaces[value] = state.spaces[value]
			})
		state.spaces = sortedSpaces
	},
	addUserToWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.name] = user
		Vue.set(state.spaces, name, space)
	},
	removeGroupFromSpace(state, { name, group }) {
		const space = state.spaces[name]
		delete space.groups[group]
		Vue.set(state.spaces, name, space)
	},
	removeUserFromWorkspace(state, { name, user }) {
		const space = state.spaces[name]
		delete space.users[user.name]
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
	},
	setSpaceQuota(state, { name, quota }) {
		const space = state.spaces[name]
		space.quota = quota
		Vue.set(state.spaces, name, space)
	},
	deleteSpace(state, { space }) {
		delete state.spaces[space.name]
	},
	updateSpace(state, space) {
		delete state.spaces[space.name]
		Vue.set(state.spaces, space.name, space)
	},
	updateUser(state, { name, user }) {
		const space = state.spaces[name]
		space.users[user.name] = user
		delete state.spaces[space.name]
		Vue.set(state.spaces, name, space)
	},
}
