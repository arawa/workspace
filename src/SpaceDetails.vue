<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
	@author 2021 Cyrille Bollu <cyrille@bollu.be>

	@license GNU AGPL version 3 or any later version

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div v-if="$store.state.noUsers">
		<NcEmptyContent
			:title="t('workspace', 'No users')">
			<template #description>
				{{ t('workspace', 'There are no users in this space/group yet') }}
			</template>
		</NcEmptyContent>
	</div>
	<div v-else-if="!$store.state.loadingUsersWaiting">
		<div class="header">
			<div class="space-name">
				<div class="space-color-picker color-dot"
					:style="{backgroundColor: $store.state.spaces[$route.params.space].color}" />
				<span class="titles-for-space">
					{{ title }}
				</span>
				<NcSelect v-model="$store.state.spaces[$route.params.space].quota"
					:class="isESR ? 'quota-select-esr' : 'quota-select'"
					:clear-search-on-select="false"
					:taggable="true"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:multiple="false"
					:clearable="false"
					:options="['1GB', '5GB', '10GB', t('workspace','unlimited')]"
					@option:selected="setSpaceQuota" />
			</div>
			<div class="space-actions">
				<div>
					<NcActions ref="ncAction" default-icon="icon-add">
						<NcActionButton icon="icon-user"
							:close-after-click="true"
							class="no-bold"
							:title="t('workspace', 'Add users')"
							@click="toggleShowSelectUsersModal" />
						<NcActionButton v-show="!createGroup"
							icon="icon-group"
							:title="t('workspace', 'Create a workspace group')"
							class="no-bold"
							@click="toggleCreateGroup" />
						<NcActionInput v-show="createGroup"
							ref="createGroupInput"
							icon="icon-group"
							:close-after-click="true"
							:show-trailing-button="true"
							@submit="onNewGroup">
							{{ t('workspace', 'Group name') }}
						</NcActionInput>
						<NcActionButton
							:name="t('workspace', 'Add a group')"
							icon="icon-added-group"
							class="no-bold"
							:close-after-click="true"
							@click="toggleShowConnectedGroups" />
					</NcActions>
					<NcActions>
						<NcActionButton icon="icon-rename"
							@click="toggleShowEditWorkspaceModal">
							{{ t('workspace', 'Edit the Workspace') }}
						</NcActionButton>
					</NcActions>
				</div>
				<NcActions v-if="$root.$data.isUserGeneralAdmin === 'true'">
					<NcActionButton icon="icon-delete"
						:close-after-click="true"
						@click="toggleShowDelWorkspaceModal">
						{{ t('workspace', 'Delete space') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>
		<UserTable :space-name="$route.params.space" />
		<NcModal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<AddUsersTabs @close-sidebar="toggleShowSelectUsersModal" />
		</NcModal>
		<SelectConnectedGroups v-if="showSelectConnectedGroups" @close="toggleShowConnectedGroups" />
		<RemoveSpace v-if="showDelWorkspaceModal"
			:space-name="$route.params.space"
			@close="toggleShowDelWorkspaceModal"
			@handle-cancel="toggleShowDelWorkspaceModal"
			@handle-delete="deleteSpace" />
		<EditWorkspace :show="showEditWorkspaceModal"
			@close="toggleShowEditWorkspaceModal" />
	</div>
</template>

<script>
import EditWorkspace from './components/Modals/EditWorkspace.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import SelectConnectedGroups from './SelectConnectedGroups.vue'
import RemoveSpace from './RemoveSpace.vue'
import UserTable from './UserTable.vue'
import { removeWorkspace } from './services/spaceService.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import AddUsersTabs from './AddUsersTabs.vue'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

export default {
	name: 'SpaceDetails',
	components: {
		AddUsersTabs,
		EditWorkspace,
		NcActions,
		NcEmptyContent,
		NcActionButton,
		NcActionInput,
		NcModal,
		NcSelect,
		SelectConnectedGroups,
		RemoveSpace,
		UserTable,
	},
	data() {
		return {
			createGroup: false, // true to display 'Create Group' ActionInput
			renameSpace: false, // true to display 'Rename space' ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
			showDelWorkspaceModal: false,
			showSelectConnectedGroups: false,
			showEditWorkspaceModal: false,
			isESR: false,
		}
	},
	computed: {
		// The title to display at the top of the page
		title() {
			return this.$route.params.space
		},
	},
	beforeMount() {
		const space = this.$store.state.spaces[this.$route.params.space]
		if (!this.$store.state.noUsers) {
			this.$store.dispatch('loadUsers', { space })
		}
	},
	beforeUpdate() {
		const space = this.$store.state.spaces[this.$route.params.space]
		if (!this.$store.state.noUsers) {
			this.$store.dispatch('loadUsers', { space })
		}
	},
	created() {
		const version = navigator.userAgent.split('Firefox/')[1]
		if (parseInt(version) < 91) {
			this.isESR = true
		}
	},
	methods: {
		// Deletes a space
		deleteSpace() {
			const space = this.$store.state.spaces[this.$route.params.space]
			removeWorkspace(space.id)
				.then(resp => {
					if (resp.http.statuscode === 200) {
						this.$store.dispatch('removeSpace', {
							space,
						})
						this.$router.push({
							path: '/',
						})
					}
				})
			this.$store.dispatch('decrementCountWorkspaces')
		},
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleCreateGroup()
			// Hide popup menu
			this.$refs.ncAction.opened = false

			// Don't accept empty names
			const gid = e.target[0].value
			if (!gid) {
				return
			}

			// Creates group
			this.$store.dispatch('createGroup', { name: this.$route.params.space, gid })
		},
		// Sets a space's quota
		setSpaceQuota(quota) {
			console.debug('setSpaceQuota')
			console.debug('quota', quota)
			if (quota === null) {
				return
			}
			const control = new RegExp(`^(${t('workspace', 'unlimited')}|\\d+(tb|gb|mb|kb)?)$`, 'i')
			if (!control.test(quota)) {
				const text = t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota')
				showNotificationError('Error', text, 3000)
				return
			}
			console.debug('coucou')
			console.debug('this.$route.params.space', this.$route.params.space)
			this.$store.dispatch('setSpaceQuota', {
				name: this.$route.params.space,
				quota,
			})
		},
		toggleCreateGroup() {
			this.createGroup = !this.createGroup
			if (this.createGroup === true) {
				this.$refs.createGroupInput.$el.focus()
			}
		},
		toggleRenameSpace() {
			this.renameSpace = !this.renameSpace
			if (this.renameSpace === true) {
				this.$refs.renameSpaceInput.$el.focus()
			}
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
		toggleShowDelWorkspaceModal() {
			this.showDelWorkspaceModal = !this.showDelWorkspaceModal
		},
		toggleShowConnectedGroups() {
			this.showSelectConnectedGroups = !this.showSelectConnectedGroups
		},
		toggleShowEditWorkspaceModal() {
			this.showEditWorkspaceModal = !this.showEditWorkspaceModal
		},
	},
}
</script>

<style>
.space-actions,
.space-color-picker,
.space-name,
.user-actions {
	display: flex;
}

.color-picker {
	margin: 0px;
}

.quota-select {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
}

.quota-select-esr {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100% !important;
}

.space-name {
	margin-left: 8px;
	margin-top: -28px;
}

.no-bold button p strong{
	font-weight: normal !important;
}

.user-actions {
	flex-flow: row-reverse;
}
.modal-wrapper--small .modal-container {
	min-height: 12rem !important;
}

</style>
