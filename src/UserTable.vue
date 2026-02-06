<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
	@author 2021 Cyrille Bollu <cyrille@bollu.be>

	@license GNU AGPL version 3 or any later version

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div>
		<table v-if="users.length" class="table-space-detail">
			<thead>
				<tr>
					<th class="workspace-th" />
					<th class="workspace-th user-info">
						{{ t('workspace', 'Users') }}
					</th>
					<th class="workspace-th role-th">
						{{ t('workspace', 'Role') }}
					</th>
					<th class="workspace-th">
						{{ t('workspace', 'Groups') }}
					</th>
					<th class="workspace-th" />
				</tr>
			</thead>
			<tbody>
				<tr v-for="user in users"
					:key="user.uid"
					:class="$store.getters.isSpaceAdmin(user, $store.getters.getSpaceByNameOrId($route.params.space)) ? 'list user-admin workspace-tr' : 'list user-simple workspace-tr'">
					<td class="avatar workspace-td">
					<lazy-component :key="user.uid">
							<NcAvatar :display-name="user.name" :user="user.uid" :show-user-status="false" />
					</lazy-component>
					</td>
					<td class="workspace-td user-info">
						<div class="user-name">
							{{ user.name }}
						</div>
						<div class="user-email">
							{{ user.email }}
						</div>
					</td>
					<td class="workspace-td">
						{{ t('workspace', $store.getters.isSpaceAdmin(user, $store.getters.getSpaceByNameOrId($route.params.space)) ? 'wm' : 'user') }}
					</td>
					<td class="workspace-td group-list">
						{{ sortGroups(user.groups) }}
					</td>
					<td class="workspace-td">
						<div class="user-actions">
							<NcActions :force-menu="true">
								<NcActionButton v-if="user.profile !== undefined"
									icon="icon-user"
									:close-after-click="true"
									@click="viewProfile(user)">
									{{ t('workspace', 'View profile') }}
								</NcActionButton>
								<NcActionButton v-if="$store.getters.isSpaceAdmin(user, $store.getters.getSpaceByNameOrId($route.params.space)) && !isCurrentUserWorkspaceManager(user)"
									icon="icon-close"
									:close-after-click="true"
									@click="toggleUserRole(user)">
									{{ t('workspace', 'Remove WM rights') }}
								</NcActionButton>
								<NcActionButton v-else-if="!$store.getters.isSpaceAdmin(user, $store.getters.getSpaceByNameOrId($route.params.space)) && !isCurrentUserWorkspaceManager(user)"
									:close-after-click="true"
									@click="toggleUserRole(user)">
									<template #icon>
										<AccountCog :size="20" />
									</template>
									{{ t('workspace', 'Assign as WM') }}
								</NcActionButton>
								<NcActionButton v-if="!$store.getters.isFromAddedGroups(user, $store.getters.getSpaceByNameOrId($route.params.space)) && !isCurrentUserWorkspaceManager(user)"
									icon="icon-delete"
									:close-after-click="true"
									@click="deleteUser(user)">
									{{ t('workspace', 'Remove user') }}
								</NcActionButton>
								<NcActionButton v-if="$route.params.slug !== undefined && isSubgroup"
									icon="icon-close"
									:close-after-click="true"
									@click="removeFromGroup(user)">
									{{ t('workspace', 'Remove from group') }}
								</NcActionButton>
							</NcActions>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<NcEmptyContent v-else>
			{{ t('workspace', 'No users') }}
			<template #desc>
				{{ t('workspace', 'There are no users in this workspace/group yet') }}
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import UserGroup from './services/Groups/UserGroup.js'
import AccountCog from 'vue-material-design-icons/AccountCog.vue'
import ManagerGroup from './services/Groups/ManagerGroup.js'
import { getLocale } from '@nextcloud/l10n'

export default {
	name: 'UserTable',
	components: {
		NcAvatar,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		AccountCog,
	},
	data() {
		return {
			createGroup: false, // true to display ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	computed: {
		// Returns the list of users to show in the table
		users() {
			let result = []
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const group = decodeURIComponent(this.$route.params.slug)
			if (this.$route.params.slug !== undefined) {
				// We are showing a group's users, so we have to filter the users
				result = Object.values(space.users)
					.filter((user) => user.groups.includes(group))
			} else {
				// We are showing all users of a workspace
				result = Object.values(space.users)
			}

			return result.sort((firstUser, secondUser) => {
				const roleFirstUser = this.$store.getters.isSpaceAdmin(firstUser, space) ? 'wm' : 'user'
				const roleSecondUser = this.$store.getters.isSpaceAdmin(secondUser, space) ? 'wm' : 'user'
				if (roleFirstUser !== roleSecondUser) {
					// display admins first
					return roleFirstUser === 'wm' ? -1 : 1
				} else {
					return firstUser.name.localeCompare(secondUser.name)
				}
			})
		},
		isSubgroup() {
			if (decodeURIComponent(decodeURIComponent(this.$route.params.slug)).startsWith('SPACE-G-')) {
				return true
			}

			// old legacy local G-
			const groupName = this.$store.getters.groupName(this.$route.params.space, decodeURIComponent(decodeURIComponent(this.$route.params.slug)))

			return groupName.startsWith('G-')
		},
	},
	methods: {
		sortGroups(groups) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const groupsSorted = this.sortedGroups([...groups], space)
			return groupsSorted.map(group => this.$store.getters.groupName(space.name, group)).join(', ')
		},
		isCurrentUserWorkspaceManager(user) {
			return this.$root.$data.isUserGeneralAdmin === false && (this.$root.$data.userSession === user.uid)
		},
		sortedGroups(groups, space) {
			groups.sort((groupCurrent, groupNext) => {
				// Makes sure the U- group is first in the list
				// These tests must happen before the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(space.name)
				if (groupCurrent === UGroup) {
					return -1
				}
				if (groupNext === UGroup) {
					return 1
				}
				// Makes sure the GE- group is second in the list
				// These tests must be done after the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(space.name)
				if (groupCurrent === GEGroup) {
					return -1
				}
				if (groupNext === GEGroup) {
					return 1
				}

				// Normal locale based sort
				// Some javascript engines don't support localCompare's locales
				// and options arguments.
				// This is especially the case of the mocha test framework
				try {
					return groupCurrent.localeCompare(groupNext, getLocale(), {
						sensitivity: 'base',
						ignorePunctuation: true,
					})
				} catch (e) {
					return groupCurrent.localeCompare(groupNext)
				}
			})
			return groups
		},
		// Removes a user from a workspace
		deleteUser(user) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			this.$store.dispatch('removeUserFromWorkspace', {
				name: space.name,
				gid: UserGroup.getGid(space),
				user,
			})
			user.groups.forEach((gid) => {
				this.$store.dispatch('decrementGroupUserCount', {
					spaceName: space.name,
					gid,
				})
			})
			this.$store.dispatch('decrementSpaceUserCount', {
				spaceName: space.name,
			})
			if (Object.keys(space.users).length === 0) {
				this.$store.dispatch('setNoUsers', { activated: true })
			}
		},
		// Makes user an admin or a simple user
		toggleUserRole(user) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const name = space.name
			this.$store.dispatch('toggleUserRole', {
				name,
				user,
			})
			if (user.is_connected) {
				this.$store.commit('TOGGLE_USER_CONNECTED', { name: space.name, user })
				this.$store.dispatch('addUserToGroup', {
					name,
					gid: UserGroup.getGid(space),
					user,
				})
			}
		},
		// Removes a user from a group
		removeFromGroup(user) {
			const gid = decodeURIComponent(this.$route.params.slug)
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			this.$store.dispatch('removeUserFromGroup', {
				name: space.name,
				gid,
				user,
			})
			this.$store.dispatch('decrementGroupUserCount', {
				spaceName: space.name,
				gid,
			})
			if (gid.startsWith('SPACE-GE')) {
				this.$store.dispatch('decrementGroupUserCount', {
					spaceName: space.name,
					gid: ManagerGroup.getGid(space),
				})
			}
			if (gid.startsWith('SPACE-U')) {
				this.$store.dispatch('decrementGroupUserCount', {
					spaceName: space.name,
					gid: UserGroup.getGid(space),
				})
				this.$store.dispatch('decrementSpaceUserCount', {
					spaceName: space.name,
				})
			}
		},
		viewProfile(user) {
			window.location.href = user.profile
		},
	},
}
</script>

<style>
.avatar {
	width: 40px;
}

.avatar > div {
	vertical-align: middle;
}

.user-actions {
	display: flex;
	flex-flow: row-reverse;
}

.user-admin {
	background-color: var(--color-primary-light);
}

.user-admin:hover {
	background-color: #f5f5f5 !important;
}

.user-simple:hover {
	background-color: #f5f5f5 !important;
}

.user-info {
	width: 30%;
	padding-left: 8px;
}

.user-name {
	font-size: large;
}

.user-email {
	color: gray;
}

.table-space-detail {
	width: 100%;
}

.group-list {
	text-wrap: wrap;
}

.role-th {
	width: 220px;
}
</style>
