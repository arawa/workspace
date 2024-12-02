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
				<tr class="workspace-tr">
					<th class="workspace-th" />
					<th class="workspace-th" style="padding-left: 15px; width: 30%;">
						{{ t('workspace', 'Users') }}
					</th>
					<th class="workspace-th">
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
					:class="$store.getters.isSpaceAdmin(user, $route.params.space) ? 'list user-admin workspace-tr' : 'list user-simple workspace-tr'">
					<td class="avatar workspace-td">
						<NcAvatar :display-name="user.name" :user="user.uid" />
					</td>
					<td style="width: 30%;" class="workspace-td">
						<div class="user-name">
							{{ user.name }}
						</div>
						<div class="user-email">
							{{ user.email }}
						</div>
					</td>
					<td class="workspace-td">
						{{ t('workspace', $store.getters.isSpaceAdmin(user, $route.params.space) ? 'admin' : 'user') }}
					</td>
					<td class="workspace-td">
						{{ user.groups.map(group => $store.getters.groupName($route.params.space, group)).join(', ') }}
					</td>
					<td class="workspace-td">
						<div class="user-actions">
							<NcActions>
								<NcActionButton v-if="$route.params.group === undefined"
									:icon="!$store.getters.isSpaceAdmin(user, $route.params.space) ? 'icon-user' : 'icon-close'"
									:close-after-click="true"
									@click="toggleUserRole(user)">
									{{
										!$store.getters.isSpaceAdmin(user, $route.params.space) ?
											t('workspace', 'Make administrator')
											: t('workspace', 'Remove admin rights')
									}}
								</NcActionButton>
								<NcActionButton v-if="$route.params.group === undefined"
									icon="icon-delete"
									:close-after-click="true"
									@click="deleteUser(user)">
									{{ t('workspace', 'Delete user') }}
								</NcActionButton>
								<NcActionButton v-if="$route.params.group !== undefined"
									icon="icon-delete"
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
				{{ t('workspace', 'There are no users in this space/group yet') }}
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import UserGroup from './services/Groups/UserGroup.js'

export default {
	name: 'UserTable',
	components: {
		NcAvatar,
		NcActions,
		NcActionButton,
		NcEmptyContent,
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
			const space = this.$store.state.spaces[this.$route.params.space]
			const group = this.$route.params.group
			if (this.$route.params.group !== undefined) {
				// We are showing a group's users, so we have to filter the users
				result = Object.values(space.users)
					.filter((user) => user.groups.includes(group))
			} else {
				// We are showing all users of a workspace
				result = Object.values(space.users)
			}

			return result.sort((firstUser, secondUser) => {
				const roleFirstUser = this.$store.getters.isSpaceAdmin(firstUser, this.$route.params.space) ? 'admin' : 'user'
				const roleSecondUser = this.$store.getters.isSpaceAdmin(secondUser, this.$route.params.space) ? 'admin' : 'user'
				if (roleFirstUser !== roleSecondUser) {
					// display admins first
					return roleFirstUser === 'admin' ? -1 : 1
				} else {
					return firstUser.name.localeCompare(secondUser.name)
				}
			})
		},
	},
	methods: {
		sortGroups(groups) {
			const spacename = this.$route.params.space
			const groupsSorted = this.sortedGroups([...groups], spacename)
			return groupsSorted.map(group => this.$store.getters.groupName(spacename, group)).join(', ')
		},
		sortedGroups(groups, spacename) {
			groups.sort((groupCurrent, groupNext) => {
				// Makes sure the GE- group is first in the list
				// These tests must happen before the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(spacename)
				if (groupCurrent === GEGroup) {
					return -1
				}
				if (groupNext === GEGroup) {
					return 1
				}
				// Makes sure the U- group is second in the list
				// These tests must be done after the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(spacename)
				if (groupCurrent === UGroup) {
					return -1
				}
				if (groupNext === UGroup) {
					return 1
				}

				return -1
			})
			return groups
		},
		// Removes a user from a workspace
		deleteUser(user) {
			const space = this.$store.state.spaces[this.$route.params.space]
			this.$store.dispatch('removeUserFromWorkspace', {
				name: this.$route.params.space,
				gid: UserGroup.getGid(space),
				user,
			})
		},
		// Makes user an admin or a simple user
		toggleUserRole(user) {
			this.$store.dispatch('toggleUserRole', {
				name: this.$route.params.space,
				user,
			})
		},
		// Removes a user from a group
		removeFromGroup(user) {
			this.$store.dispatch('removeUserFromGroup', {
				name: this.$route.params.space,
				gid: this.$route.params.group,
				user,
			})
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

.user-name {
	font-size: large;
}

.user-email {
	color: gray;
	padding-left: 10px;
}

.table-space-detail {
	width: 100%;
	margin-top: -25px;
}
</style>
