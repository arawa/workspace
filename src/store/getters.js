export const getters = {
	// Returns the groups of a space
	groups: state => spaceName => {
		return Object.keys(state.spaces[spaceName].groups)
	},
	// Returns the number of users in a group
	groupUserCount: state => (spaceName, groupName) => {
		const users = state.spaces[spaceName].users
		if (users.length === 0) {
			return 0
		} else {
			// We count all users in the space who have 'groupName' listed in their 'groups' property
			return Object.values(users).filter(user => user.groups.includes(groupName)).length
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
}
