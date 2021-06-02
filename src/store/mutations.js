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
	addUserToAdminList(state, { spaceName, user }) {
		const space = state.spaces[spaceName]
		space.admins[user.name] = user
		Vue.set(state.spaces, spaceName, space)
 	},
 		addUserToUserList(state, { spaceName, user }) {
		const space = state.spaces[spaceName]
		space.users[user.name] = user
		Vue.set(state.spaces, spaceName, space)
	},
	removeGroupFromSpace(state, { name, group }) {
		const space = state.spaces[name]
		delete space.groups[group]
		Vue.set(state.spaces, name, space)
	},
	removeUserFromAdminList(state, { spaceName, user }) {
		const space = state.spaces[spaceName]
		delete space.admins[user.name]
		Vue.set(state.spaces, spaceName, space)
	},
	removeUserFromUserList(state, { spaceName, user }) {
		const space = state.spaces[spaceName]
		delete space.users[user.name]
		Vue.set(state.spaces, spaceName, space)
	},
	setSpaceQuota(state, { name, quota }) {
		const space = state.spaces[name]
		space.quota = quota
		Vue.set(state.spaces, name, space)
	},
}
