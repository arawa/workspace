export const getters = {
	// Returns the GE group of a workspace
	GEGroup: state => name => {
		const groups = Object.values(state.spaces[name].groups).filter(group => {
			// TODO use constants
			return group.displayName === 'GE-' + name
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
	// Tests whether a user is member of workspace
	isMember: state => (name, user) => {
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
	sortedSpaces: state => {
		const sortedSpaces = {}
		Object.keys(state.spaces).sort().forEach((value, index) => {
			sortedSpaces[value] = state.spaces[value]
		})
		return sortedSpaces
	},
	// Returns the U- group of a workspace
	UGroup: state => name => {
		const groups = Object.values(state.spaces[name].groups).filter(group => {
			// TODO use constants
			return group.displayName === 'U-' + name
		})
		return groups[0]
	},
}
