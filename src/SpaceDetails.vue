<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<div class="header">
			<div class="space-name">
				<span class="space-title">
					{{ space.name }}
				</span>
			</div>
			<div class="space-actions">
				<div>
					<Actions>
						<ActionButton
							icon="icon-add"
							@click="toggleShowSelectUsersModal" />
					</Actions>
				</div>
				<Actions>
					<ActionButton
						icon="icon-rename"
						@click="renameSpace">
						{{ t('workspace', 'Rename space') }}
					</ActionButton>
					<ActionButton
						icon="icon-delete"
						@click="deleteSpace">
						{{ t('workspace', 'Delete space') }}
					</ActionButton>
				</Actions>
			</div>
		</div>
		<div class="space-details">
			<table>
				<thead>
					<tr>
						<th>{{ t('workspace', 'Users') }}</th>
						<th>{{ t('workspace', 'Role') }}</th>
						<th>{{ t('workspace', 'Email') }}</th>
						<th />
					</tr>
				</thead>
				<tbody>
					<tr v-for="user in space.users"
						:key="user.name">
						<td> {{ user.name }} </td>
						<td> {{ t('workspace', user.role) }} </td>
						<td> {{ user.email }} </td>
						<td>
							<Actions class="user-actions">
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
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<Modal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<SelectUsers />
		</Modal>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'

export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
		Modal,
		SelectUsers,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			showSelectUsersModal: false,
		}
	},
	methods: {
		// Make user an admin or a simple user
		toggleUserRole(user) {
			// this.space.users[user].role = user.role === 'admin' ? 'user' : 'admin'
			// TODO: update backend
		},
		setUserAdmin() {
			// TODO
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
	},
}
</script>

<style>
.space-actions {
	display: flex;
}
.user-actions {
	display: flex;
	justify-content: end;
}
.space-title {
	font-weight: bold;
	font-size: x-large;
}
</style>
