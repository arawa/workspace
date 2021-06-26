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
			<div class="group-name">
				<div class="icon-group" />
				<span class="group-title">
					{{ $route.params.group }}
				</span>
			</div>
			<div class="group-actions">
				<div>
					<Actions default-icon="icon-add">
						<ActionButton
							icon="icon-user"
							:close-after-click="true"
							:title="t('workspace', 'Add users')"
							@click="toggleShowSelectUsersModal" />
						<ActionButton v-show="!showCreateGroupInput"
							icon="icon-group"
							:title="t('workspace', 'Create group')"
							@click="toggleShowCreateGroupInput" />
						<ActionInput v-show="showCreateGroupInput"
							ref="createGroupInput"
							icon="icon-group"
							@submit="onNewGroup">
							{{ t('workspace', 'Group name') }}
						</ActionInput>
					</Actions>
				</div>
				<Actions>
					<ActionButton
						icon="icon-rename"
						@click="renameGroup">
						{{ t('workspace', 'Rename group') }}
					</ActionButton>
					<ActionButton
						icon="icon-delete"
						@click="deleteGroup">
						{{ t('workspace', 'Delete group') }}
					</ActionButton>
				</Actions>
			</div>
		</div>
		<UserTable :space-name="$route.params.group" />
		<Modal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<SelectUsers :space-name="$route.params.group" @close="toggleShowSelectUsersModal" />
		</Modal>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'
import UserTable from './UserTable'

export default {
	name: 'GroupDetails',
	components: {
		Actions,
		ActionButton,
		ActionInput,
		Modal,
		SelectUsers,
		UserTable,
	},
	data() {
		return {
			showCreateGroupInput: false, // true to display ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	methods: {
		deleteGroup() {
			this.$store.dispatch('deleteGroup', { name: this.$route.params.space, group: this.$route.params.group })
		},
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleShowCreateGroupInput()

			// Don't accept empty names
			const group = e.target[1].value
			if (!group) {
				return
			}

			// Create group
			this.$store.dispatch('createGroup', { name: this.$route.params.space, group })

		},
		renameGroup() {
			// TODO
		},
		toggleShowCreateGroupInput() {
			this.showCreateGroupInput = !this.showCreateGroupInput
			if (this.showCreateGroupInput === true) {
				this.$refs.createGroupInput.$el.focus()
			}
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
	},
}
</script>

<style>
.icon-group {
	min-width: 42px;
	min-height: 42px;
}

.group-actions,
.group-name,
.user-actions {
	display: flex;
}

.user-actions {
	flex-flow: row-reverse;
}

.group-title {
	font-weight: bold;
	font-size: xxx-large;
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
