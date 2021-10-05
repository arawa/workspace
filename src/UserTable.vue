<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<table v-if="users.length" class="table-space-detail">
			<thead>
				<tr>
					<th />
					<th style="padding-left: 15px; width: 30%;">
						{{ t('workspace', 'Users') }}
					</th>
					<th>{{ t('workspace', 'Role') }}</th>
					<th>{{ t('workspace', 'Groups') }}</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="user in users"
					:key="user.uid"
					:class="$store.getters.isSpaceAdmin(user, $route.params.space) ? 'user-admin list-users' : 'list-users'">
					<td class="avatar">
						<Avatar :display-name="user.name" :user="user.uid" />
					</td>
					<td style="width: 30%;">
						<div class="user-name">
							{{ user.name }}
						</div>
						<div class="user-email">
							{{ user.email }}
						</div>
					</td>
					<td> {{ t('workspace', $store.getters.isSpaceAdmin(user, $route.params.space) ? 'admin' : 'user') }} </td>
					<td> {{ user.groups.map(group => $store.getters.groupName($route.params.space, group)).join(', ') }} </td>
					<td>
						<div class="user-actions">
							<Actions>
								<ActionButton v-if="$route.params.group === undefined"
									:icon="!$store.getters.isSpaceAdmin(user, $route.params.space) ? 'icon-user' : 'icon-close'"
									:close-after-click="true"
									@click="toggleUserRole(user)">
									{{
										!$store.getters.isSpaceAdmin(user, $route.params.space) ?
											t('workspace', 'Make administrator')
											: t('workspace', 'Remove admin rights')
									}}
								</ActionButton>
								<ActionButton v-if="$route.params.group === undefined"
									icon="icon-delete"
									:close-after-click="true"
									@click="deleteUser(user)">
									{{ t('workspace', 'Delete user') }}
								</ActionButton>
								<ActionButton v-if="$route.params.group !== undefined"
									icon="icon-delete"
									:close-after-click="true"
									@click="removeFromGroup(user)">
									{{ t('workspace', 'Remove from group') }}
								</ActionButton>
							</Actions>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<EmptyContent v-else>
			{{ t('workspace', 'No users') }}
			<template #desc>
				{{ t('workspace', 'There are no users in this space/group yet') }}
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import { ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from './constants'

export default {
	name: 'UserTable',
	components: {
		Avatar,
		Actions,
		ActionButton,
		EmptyContent,
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
		// Removes a user from a workspace
		deleteUser(user) {
			const space = this.$store.state.spaces[this.$route.params.space]
			this.$store.dispatch('removeUserFromGroup', {
				name: this.$route.params.space,
				gid: ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.id,
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
