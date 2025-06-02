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
			:name="t('workspace', 'No users')">
			<template #description>
				{{ t('workspace', 'There are no users in this space/group yet') }}
			</template>
		</NcEmptyContent>
	</div>
	<div v-else-if="!$store.state.loadingUsersWaiting">
		<div class="header">
			<div class="space-name">
				<div class="space-color-picker color-dot"
					:style="{backgroundColor: $store.getters.getSpaceByNameOrId($route.params.space).color}" />
				<span class="titles-for-space">
					{{ title }}
				</span>
				<NcSelect v-model="getQuota"
					:class="isESR ? 'quota-select-esr' : 'quota-select'"
					:disabled="true"
					:multiple="false"
					:clearable="false" />
			</div>
			<div class="space-actions">
				<NcActions ref="ncAction" default-icon="icon-add">
					<NcActionButton icon="icon-user"
						:close-after-click="true"
						class="no-bold"
						:name="t('workspace', 'Add users')"
						@click="toggleShowSelectUsersModal" />
					<NcActionButton v-show="!createGroup"
						icon="icon-group"
						:name="t('workspace', 'Create a workspace group')"
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
		<NcDialog v-if="showSelectUsersModal"
			:name="t('workspace', 'Add users')"
			size="normal"
			@update:open="toggleShowSelectUsersModal">
			<AddUsersTabs />
		</NcDialog>
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
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import SelectConnectedGroups from './SelectConnectedGroups.vue'
import RemoveSpace from './RemoveSpace.vue'
import UserTable from './UserTable.vue'
import { removeWorkspace } from './services/spaceService.js'
import AddUsersTabs from './AddUsersTabs.vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

export default {
	name: 'SpaceDetails',
	components: {
		AddUsersTabs,
		EditWorkspace,
		NcActions,
		NcEmptyContent,
		NcActionButton,
		NcActionInput,
		NcDialog,
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
			return this.$store.getters.getSpaceByNameOrId(this.$route.params.space).name
		},
		getQuota() {
			return this.$store.getters.convertQuotaForFrontend(this.$store.getters.getSpaceByNameOrId(this.$route.params.space).quota)
		},
	},
	beforeMount() {
		const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
		if (!this.$store.state.noUsers) {
			this.$store.dispatch('loadUsers', { space })
		}
	},
	beforeUpdate() {
		const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
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
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
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
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			this.$store.dispatch('createGroup', { space, gid })
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
    width: 100%;
    flex-grow: 1;
    display: inline-flex;
    align-items: center;
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
