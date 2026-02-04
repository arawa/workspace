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

import { t } from '@nextcloud/l10n'
import ManagerGroup from '../services/Groups/ManagerGroup.js'
import UserGroup from '../services/Groups/UserGroup.js'

export const getters = {
	// Returns the GE group of a workspace
	GEGroup: (state, getters) => spaceNameOrId => {
		return ManagerGroup.getGid(getters.getSpaceByNameOrId(spaceNameOrId))
	},
	// Returns the name of a group
	groupName: (state, getters) => (spaceNameOrId, gid) => {
		const space = getters.getSpaceByNameOrId(spaceNameOrId)
		if (space) {
			if (space.groups[gid]) {
				return space.groups[gid].displayName
			}
			if (space.added_groups[gid]) {
				return space.added_groups[gid].displayName
			}
		}
		return '[' + gid + ']'
	},
	getGroupUserCount: (state, getters) => (spaceNameOrId, gid) => {
		return getters.getSpaceByNameOrId(spaceNameOrId).groups[gid].usersCount
	},
	getSpaceUserCount: (state, getters) => (spaceNameOrId) => {
		return getters.getSpaceByNameOrId(spaceNameOrId).usersCount
	},
	getSpaceById: state => (spaceId) => {
		return Object.values(state.spaces).find((space) => space.id === spaceId)
	},
	getSpaceByName: state => (spaceName) => {
		return state.spaces[spaceName]
	},
	getSpaceByNameOrId: (state, getters) => spaceName => {
		return state.spaces[spaceName] || getters.getSpaceById(Number(spaceName))
	},
	// Returns the number of users in a group
	groupUserCount: (state, getters) => (space, gid) => {
		const users = space.users
		if (users.length === 0) {
			return 0
		} else {
			// Counts all users in the space who have 'gid' listed in their 'groups' property
			return Object.values(users).filter(user => user.groups.includes(gid)).length
		}
	},
	// Tests wheter a user if General manager of a space
	isSpaceAdmin: state => (user, space) => {
		if (!space) {
			return false
		}
		return user.groups.includes(ManagerGroup.getGid(space))
	},
	// Test whether a user if from and added group from the space
	isFromAddedGroups: state => (user, space) => {
		const addedGroups = Object.keys(space.added_groups)
		const hasAddedGroups = user.groups.filter((group) => addedGroups.includes(group))
		return hasAddedGroups.length > 0
	},
	// Test if group is from space added groups
	isSpaceAddedGroup: (state, getters) => (spaceNameOrId, groupName) => {
		const space = getters.getSpaceByNameOrId(spaceNameOrId)
		const gids = Object.keys(space.added_groups)
		return gids.includes(groupName)
	},
	// Tests wheter a group is the GE or U group of a space
	isGEorUGroup: (state, getters) => (spaceNameOrId, gid) => {
		return gid === getters.GEGroup(spaceNameOrId) || gid === getters.UGroup(spaceNameOrId)
	},
	// Tests whether a user is member of workspace
	isMember: (state, getters) => (spaceNameOrId, user) => {
		const space = getters.getSpaceByNameOrId(spaceNameOrId)
		if (!space) {
			return false
		}
		const users = space.users
		if (users.length === 0) {
			return false
		} else {
			return (user.uid in users)
		}
	},
	convertQuotaForFrontend: state => quota => {
		if (quota === -3 || quota === '-3' || quota === undefined) {
			return t('workspace', 'unlimited')
		} else if (quota === 0) {
			return '0 GB'
		} else {
			const units = ['', 'KB', 'MB', 'GB', 'TB']
			let i = 0
			while (quota >= 1024) {
				quota = quota / 1024
				i++
			}
			console.debug('Converted quota: ' + quota + ' ' + units[i])
			return Number(quota.toFixed(2)) + ' ' + units[i]
		}
	},
	convertQuotaToByte: state => quota => {
		switch (quota.substr(-2).toLowerCase()) {
		case 'tb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 4
			break
		case 'gb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 3
			break
		case 'mb':
			quota = quota.substr(0, quota.length - 2) * 1024 ** 2
			break
		case 'kb':
			quota = quota.substr(0, quota.length - 2) * 1024
			break
		}
		quota = (quota === t('workspace', 'unlimited')) ? -3 : quota

		return quota
	},
	getSpacename: state => name => {
		return state.spaces[name].name
	},
	// Returns the number of users in a space
	spaceUserCount: (state, getters) => space => {
		if (space === undefined || space === null) {
			return 0
		}
		if (space.usersCount !== undefined && space.usersCount > 0) {
			return space.usersCount
		}
		const users = space.users
		if (users === undefined || users.length === 0) {
			return -1
		} else {
			return Object.keys(users).length
		}
	},
	// Returns the U- group of a workspace
	UGroup: (state, getters) => spaceNameOrId => {
		return UserGroup.getGid(getters.getSpaceByNameOrId(spaceNameOrId))
	},
	getWorkspaceManagerUsers: (state, getters) => spacename => {
		const space = state.spaces[spacename]
		if (space.managers) {
			return Object.values(space.managers)
		}
		return Object.values(space.users).filter((u) => getters.isSpaceAdmin(u, space))
	},
	getFirstTenWorkspaceManagerUsers: (state, getters) => spacename => {
		const users = getters.getWorkspaceManagerUsers(spacename)
		return users.slice(0, 10)
	},
	getLatestWorkspaceManagerUsers: (state, getters) => spacename => {
		const users = getters.getWorkspaceManagerUsers(spacename)
		const lastUsers = users.slice(10)
		const lastUsersStingify = lastUsers.map((user) => user.name)
		return lastUsersStingify.join(', ')
	},
	countWorkspaceManagerUsersAboveThreshold: (state, getters) => spacename => {
		const users = getters.getWorkspaceManagerUsers(spacename)
		return users.slice(10).length
	},
	countWorkspaces: (state, getters) => {
		return state.countWorkspaces
	},
	countTotalWorkspaces: (state, getters) => {
		return state.countTotalWorkspaces
	},
	countTotalWorkspacesByQuery: (state, getters) => {
		return state.countTotalWorkspacesByQuery
	},
	workspaceCurrentPage: (state, getters) => {
		return state.workspaceCurrentPage
	},
	searchWorkspace: (state, getters) => {
		return state.searchWorkspace
	},
	nextPage: (state, getters) => {
		return state.nextPage
	},
}
