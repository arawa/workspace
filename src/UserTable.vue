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
				<tr v-for="user in $root.$data.spaces[spaceName].users"
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
	props: {
		spaceName: {
			type: String,
			required: true,
		},
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
			const space = this.$root.$data.spaces[this.spaceName]
			space.users.every(u => {
				if (u.name === user.name) {
					user.role = (user.role === 'admin') ? 'user' : 'admin'
					return false
				}
				return true
			})
			Vue.set(this.$root.$data.spaces, this.spaceName, space)
			// TODO: update backend
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
