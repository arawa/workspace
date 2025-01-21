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

import ManagerGroup from '../services/Groups/ManagerGroup.js'
import UserGroup from '../services/Groups/UserGroup.js'

export const getters = {
	// Returns the GE group of a workspace
	GEGroup: state => name => {
		return ManagerGroup.getGid(state.spaces[name])
	},
	// Returns the name of a group
	groupName: state => (name, gid) => {
		if (state.spaces[name].groups[gid]) {
			return state.spaces[name].groups[gid].displayName
		}
		if (state.spaces[name].added_groups[gid]) {
			return state.spaces[name].added_groups[gid].displayName
		}
		return '[' + gid + ']'
	},
	getGroupUserCount: state => (spaceName, gid) => {
		return state.spaces[spaceName].groups[gid].usersCount
	},
	getSpaceUserCount: state => (name) => {
		return state.spaces[name].userCount
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
	// Tests wheter a user if General manager of a space
	isSpaceAdmin: state => (user, spaceName) => {
		return user.groups.includes(ManagerGroup.getGid(state.spaces[spaceName]))
	},
	// Test whether a user if from and added group from the space
	isFromAddedGroups: state => (user, spaceName) => {
		const addedGroups = Object.keys(state.spaces[spaceName].added_groups)
		const hasAddedGroups = user.groups.filter((group) => addedGroups.includes(group))
		return hasAddedGroups.length > 0
	},
	// Test if group is from space added groups
	isSpaceAddedGroup: state => (spaceName, groupName) => {
		const space = state.spaces[spaceName]
		const gids = Object.keys(space.added_groups)
		return gids.includes(groupName)
	},
	// Tests wheter a group is the GE or U group of a space
	isGEorUGroup: (state, getters) => (spaceName, gid) => {
		return gid === getters.GEGroup(spaceName) || gid === getters.UGroup(spaceName)
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
		if (state.spaces[name] === undefined) {
			return 0
		}
		if (state.spaces[name].userCount !== undefined && state.spaces[name].userCount > 0) {
			return state.spaces[name].userCount
		}
		const users = state.spaces[name].users
		if (users === undefined || users.length === 0) {
			return -1
		} else {
			return Object.keys(users).length
		}
	},
	// Returns the U- group of a workspace
	UGroup: state => name => {
		return UserGroup.getGid(state.spaces[name])
	},
}
