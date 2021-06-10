import Vue from 'vue'

export default {
	addGroupToSpace(state, { name, group }) {
		const space = state.spaces[name]
		space.groups[group] = group
		Vue.set(state.spaces, name, space)
	},
	addSpace(state, space) {
		Vue.set(state.spaces, space.name, space)
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
}
