<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<div id="space-header">
			<div id="space-name">
				<span>
					{{ space.name }}
				</span>
			</div>
			<div>
				<Actions>
					<ActionButton
						icon="icon-add"
						@click="toggleShowSelectUsersModal" />
				</Actions>
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
		<div id="space-details">
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
							<Actions>
								<ActionButton
									icon="icon-delete"
									@click="deleteUser">
									{{ t('worksapce', 'Delete user') }}
								</ActionButton>
								<ActionButton
									icon="icon-user"
									@click="setUserAdmin">
									{{ t('workspace', 'Make administrator') }}
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
		deleteUser() {
			// TODO
		},
		setUserAdmin() {
			// TODO
		},
		toggleShowSelectUsersModal() {
			// eslint-disable-next-line
			console.log('show', this.showSelectUsersModal)
			this.showSelectUsersModal = !this.showSelectUsersModal
		}
	},
}
</script>
