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
					<th>{{ t('workspace', 'Users') }}</th>
					<th>{{ t('workspace', 'Role') }}</th>
					<th>{{ t('workspace', 'Groups') }}</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="user in workspaceUsers($route.params.space)"
					:key="user.name"
					:class="user.role==='admin' ? 'user-admin' : ''">
					<td>
						<div class="user-name">
							{{ user.name }}
						</div>
						<div class="user-email">
							{{ user.email }}
						</div>
					</td>
					<td> {{ t('workspace', user.role) }} </td>
					<td> user groups should go here </td>
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
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Vue from 'vue'

export default {
	name: 'UserTable',
	components: {
		Actions,
		ActionButton,
	},
	data() {
		return {
			createGroup: false, // true to display ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	methods: {
		deleteUser() {
			// TODO
		},
		// Makes user an admin or a simple user
		toggleUserRole(user) {
			const space = this.$root.$data.spaces[this.$route.params.space]
			space.users.every(u => {
				if (u.name === user.name) {
					user.role = (user.role === 'admin') ? 'user' : 'admin'
					return false
				}
				return true
			})
			Vue.set(this.$root.$data.spaces, this.$route.params.space, space)
			// TODO: update backend
		},
		// Returns the users of the workspace in a format suitable for this component
		workspaceUsers(name) {
			const space = this.$root.$data.spaces[name]
			let allUsers = []
			// Let's first process the admins
			let users = Array.isArray(space.admins) ? [] : Object.keys(space.admins)
			allUsers = users.map((user) => {
				return {
					name: user,
					role: 'admin',
				}
			})
			// And then the regular users
			users = Array.isArray(space.users) ? [] : Object.keys(space.users)
			allUsers = [...allUsers, ...users.map((user) => {
				return {
					name: user,
					role: 'user',
				}
			})]

			return allUsers
		},
	},
}
</script>

<style>
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
