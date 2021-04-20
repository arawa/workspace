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
					{{ spaceName }}
				</span>
				<Multiselect
					class="quota-select"
					tag-placeholder="t('workspace', 'Add specific quota')"
					:taggable="true"
					:value="$root.$data.spaces[spaceName].quota"
					:options="['1GB', '5GB', '10GB', 'unlimited']"
					@change="setSpaceQuota"
					@tag="setSpaceQuota" />
			</div>
			<div class="space-actions">
				<div>
					<Actions default-icon="icon-add">
						<ActionButton
							icon="icon-user"
							:title="t('workspace', 'Add user')"
							@click="toggleShowSelectUsersModal" />
						<ActionInput
							icon="icon-group"
							@submit="onNewGroup">
							{{ t('workspace', 'Create group') }}
						</ActionInput>
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
						<th>{{ t('workspace', 'Groups') }}</th>
						<th />
					</tr>
				</thead>
				<tbody>
					<tr v-for="user in $root.$data.spaces[spaceName].users"
						:key="user.name">
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
		<Modal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<SelectUsers :space-name="spaceName" @close="toggleShowSelectUsersModal" />
		</Modal>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'
import Vue from 'vue'

export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
		ActionInput,
		Modal,
		Multiselect,
		SelectUsers,
	},
	props: {
		spaceName: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			showSelectUsersModal: false,
		}
	},
	methods: {
		deleteSpace() {
			// TODO
		},
		deleteUser() {
			// TODO
		},
		onNewGroup() {
			// TODO
		},
		renameSpace() {
			// TODO
		},
		// Set a space's quota
		setSpaceQuota(quota) {
			const space = this.$root.$data.spaces[this.spaceName]
			space.quota = quota
			Vue.set(this.$root.$data.spaces, this.spaceName, space)
		},
		setUserAdmin() {
			// TODO
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
		// Make user an admin or a simple user
		toggleUserRole(name, user) {
			const space = this.$root.$data.spaces[name]
			space.users[user].role = user.role === 'admin' ? 'user' : 'admin'
			Vue.set(this.$root.$data.spaces, name, space)
			// TODO: update backend
		},
	},
}
</script>

<style>
.space-actions,
.space-name,
.user-actions {
	display: flex;
}

.user-actions {
	flex-flow: row-reverse;
}

.quota-select {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
}

.space-title {
	font-weight: bold;
	font-size: xxx-large;
}

.user-name {
	font-size: large;
}

.user-email {
	color: gray;
	padding-left: 10px;
}
</style>
