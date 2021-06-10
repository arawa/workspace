<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<table>
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
					:key="user.name"
					:class="user.role==='admin' ? 'user-admin' : ''">
					<td class="avatar">
						<Avatar :display-name="user.name" :user="user.name" />
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
					<td> {{ user.groups.join(', ') }} </td>
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
	</div>
</template>

<script>
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

export default {
	name: 'UserTable',
	components: {
		Avatar,
		Actions,
		ActionButton,
	},
	data() {
		return {
			createGroup: false, // true to display ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
			users: [], // the users to be listed in the component
		}
	},
	watch: {
		$route(params) {
			this.getUsers()
		},
	},
	created() {
		this.getUsers()
	},
	methods: {
		// Remove a user's access to a workspace
		deleteUser(user) {
			this.$store.dispatch('removeUserFromSpace', {
				spaceName: this.$route.params.space,
				user,
			})
		},
		// Gets users to be listed
		getUsers() {
			const space = this.$store.state.spaces[this.$route.params.space]
			const group = this.$route.params.group
			if (this.$route.params.group !== undefined) {
				// We are showing a group's users, so we have to filter the users
				this.users = Object.values(space.users)
					.filter((user) => user.groups.includes(group))
					.sort((a, b) => a.name.localeCompare(b.name))
			} else {
				// We are showing all users of a workspace
				this.users = Object.values(space.users).sort((a, b) => a.name.localeCompare(b.name))
			}
		},
		// Makes user an admin or a simple user
		toggleUserRole(user) {
			this.$store.dispatch('toggleUserRole', {
				spaceName: this.$route.params.space,
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
