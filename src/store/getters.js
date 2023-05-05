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

import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from '../constants.js'

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
	// Tests wheter a user if General manager of a space
	isSpaceAdmin: state => (user, spaceName) => {
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
