<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<table v-if="users.length">
			<thead>
				<tr>
					<th colspan="2">
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
					:class="user.role==='admin' ? 'user-admin' : ''">
					<td class="avatar">
						<Avatar :display-name="user.name" :user="user.uid" />
					</td>
					<td>
						<div class="user-name">
							{{ user.name }}
						</div>
						<div class="user-email">
							{{ user.email }}
						</div>
					</td>
					<td> {{ t('workspace', user.role) }} </td>
					<td> {{ user.groups.map(group => $store.getters.groupName($route.params.space, group)).join(', ') }} </td>
					<td>
						<div class="user-actions">
							<Actions>
								<ActionButton
									:icon="user.role === 'user' ? 'icon-user' : 'icon-close'"
									:close-after-click="true"
									@click="toggleUserRole(user)">
									{{
										user.role === 'user' ?
											t('workspace', 'Make administrator')
											: t('workspace', 'Remove admin rights')
									}}
								</ActionButton>
								<ActionButton
									icon="icon-delete"
									:close-after-click="true"
									@click="deleteUser(user)">
									{{ t('workspace', 'Delete user') }}
								</ActionButton>
							</Actions>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<EmptyContent v-else>
			No users
			<template #desc>
				There are no users in this space/group yet
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

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
		users() {
			let result = []
			const space = this.$store.state.spaces[this.$route.params.space]
			const group = this.$route.params.group
			if (this.$route.params.group !== undefined) {
				// We are showing a group's users, so we have to filter the users
				result = Object.values(space.users)
					.filter((user) => user.groups.includes(group))
					.sort((a, b) => {
						// display admins first
						if (a.role !== b.role) {
							return a.role === 'admin' ? -1 : 1
						} else {
							return a.name.localeCompare(b.name)
						}
					})
			} else {
				// We are showing all users of a workspace
				result = Object.values(space.users).sort((a, b) => {
					// display admins first
					if (a.role !== b.role) {
						return a.role === 'admin' ? -1 : 1
					} else {
						return a.name.localeCompare(b.name)
					}
				})
			}
			return result
		},
	},
	methods: {
		// Remove a user's access to a workspace
		deleteUser(user) {
			this.$store.dispatch('removeUserFromSpace', {
				name: this.$route.params.space,
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
	},
}
</script>

<style>
.avatar {
	width: 40px;
}

.user-actions {
	display: flex;
	flex-flow: row-reverse;
}

.user-admin {
	background-color: #F7FBFE;
}

.user-name {
	font-size: large;
}

.user-email {
	color: gray;
	padding-left: 10px;
}
</style>
