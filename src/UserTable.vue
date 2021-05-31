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
									@click="toggleUserRole(user)">
									{{
										user.role === 'user' ?
											t('workspace', 'Make administrator')
											: t('workspace', 'Remove admin rights')
									}}
								</ActionButton>
								<ActionButton
									icon="icon-delete"
									@click="deleteUser">
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
import Vue from 'vue'

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
		}
	},
	computed: {
		users() {
			let result = []
			if (this.$route.params.group !== undefined) {
				// We are showing a group's users, so we have to filter the users
				const space = this.$store.state.spaces[this.$route.params.space]
				const group = this.$route.params.group
				result = Object.entries(space.admins)
					.map(user => user[1])
					.filter((user) => user.groups.includes(group))
					.sort((a, b) => a.name.localeCompare(b.name))
				result = [...result, ...Object.entries(space.users)
					.map(user => user[1])
					.filter((user) => user.groups.includes(group))
					.sort((a, b) => a.name.localeCompare(b.name))]
			} else {
				// We are showing all users of a workspace
				result = Object.entries(space.admins).map(u => u[1]).sort((a, b) => a.name.localeCompare(b.name))
				result = [...result, ...Object.entries(space.users).map(u => u[1]).sort((a, b) => a.name.localeCompare(b.name))]
			}
			return result
		},
	},
	methods: {
		deleteUser() {
			// TODO
		},
		// Makes user an admin or a simple user
		toggleUserRole(user) {
			const space = this.$store.state.spaces[this.$route.params.space]
			space.users.every(u => {
				if (u.name === user.name) {
					user.role = (user.role === 'admin') ? 'user' : 'admin'
					return false
				}
				return true
			})
			Vue.set(this.$store.state.spaces, this.$route.params.space, space)
			// TODO: update backend
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
