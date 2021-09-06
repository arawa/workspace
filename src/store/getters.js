import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from '../constants'

export const getters = {
	// Returns the GE group of a workspace
	GEGroup: state => name => {
		const groups = Object.values(state.spaces[name].groups).filter(group => {
			return group.gid === ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + state.spaces[name].id
		})
		return groups[0]
	},
	// Returns the name of a group
	groupName: state => (name, gid) => {
		return state.spaces[name].groups[gid].displayName
	},
	// Returns the number of users in a group
	groupUserCount: state => (spaceName, gid) => {
		const users = state.spaces[spaceName].users
		if (users.length === 0) {
			return 0
		} else {
			// Counts all users in the space who have 'gid' listed in their 'groups' property
			return Object.values(users).filter(user => user.groups.includes(gid)).length
		}
	},
	// Tests wheter a user if GE of a space
	isGE: state => (user, spaceName) => {
		return user.groups.includes(ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + state.spaces[spaceName].id)
	},
	// Tests wheter a group is the GE or U group of a space
	isGEorUGroup: (state, getters) => (spaceName, gid) => {
		if (gid === getters.GEGroup(spaceName).gid
		|| gid === getters.UGroup(spaceName).gid) {
			return true
		}
		return false
	},
	// Tests whether a user is member of workspace
	isMember: state => (name, user) => {
		const users = state.spaces[name].users
		if (users.length === 0) {
			return false
		} else {
			return (user.uid in users)
		}
	},
	isNewMember: state => (name, group, user) => {
		const REGEXP = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + '|^' + ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX)
		if (group === undefined) {
			return true
		}
		if (REGEXP.test(group)) {
			return true
		}
		const users = state.spaces[name].users
		if (users.length === 0) {
			return false
		} else {
			return (user.uid in users)
		}
	},
	// Returns the quota of a space
	quota: state => spaceName => {
		return state.spaces[spaceName].quota
	},
	// Returns the number of users in a space
	spaceUserCount: state => name => {
		const users = state.spaces[name].users
		if (users.length === 0) {
			return 0
		} else {
			return Object.keys(users).length
		}
	},
	// Returns the U- group of a workspace
	UGroup: state => name => {
		const groups = Object.values(state.spaces[name].groups).filter(group => {
			return group.gid === ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + state.spaces[name].id
		})
		return groups[0]
	},
}
