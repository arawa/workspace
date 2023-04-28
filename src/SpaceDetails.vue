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
	<div>
		<div class="header">
			<div class="space-name">
				<NcColorPicker v-model="$store.state.spaces[$route.params.space].color" class="space-color-picker" @input="updateColor">
					<button class="color-dot color-picker" :style="{backgroundColor: $store.state.spaces[$route.params.space].color}" />
				</NcColorPicker>
				<span class="titles-for-space">
					{{ title }}
				</span>
				<NcMultiselect :class="isESR ? 'quota-select-esr' : 'quota-select'"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:taggable="true"
					:value="$store.state.spaces[$route.params.space].quota"
					:options="['1GB', '5GB', '10GB', t('workspace','unlimited')]"
					@change="setSpaceQuota"
					@tag="setSpaceQuota" />
			</div>
			<div class="space-actions">
				<div>
					<NcActions default-icon="icon-add">
						<NcActionButton icon="icon-user"
							:close-after-click="true"
							class="no-bold"
							:title="t('workspace', 'Add users')"
							@click="toggleShowSelectUsersModal" />
						<NcActionButton v-show="!createGroup"
							icon="icon-group"
							:title="t('workspace', 'Create group')"
							class="no-bold"
							@click="toggleCreateGroup" />
						<NcActionInput v-show="createGroup"
							ref="createGroupInput"
							icon="icon-group"
							:close-after-click="true"
							@submit="onNewGroup">
							{{ t('workspace', 'Group name') }}
						</NcActionInput>
					</NcActions>
				</div>
				<NcActions v-if="$root.$data.isUserGeneralAdmin === 'true'">
					<NcActionButton v-show="!renameSpace"
						icon="icon-rename"
						:title="t('workspace', 'Rename space')"
						class="no-bold"
						@click="toggleRenameSpace" />
					<NcActionInput v-show="renameSpace"
            ref="renameSpaceInput"
						icon="icon-rename"
						@submit="onSpaceRename">
						{{ t('workspace', 'Space name') }}
					</NcActionInput>
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
			<SelectUsers :space-name="$route.params.space" @close="toggleShowSelectUsersModal" />
		</NcModal>
		<NcModal v-if="showDelWorkspaceModal"
      style="min-heigth: 8rem;"
      size="small"
		  @close="toggleShowDelWorkspaceModal">
		  <RemoveSpace :space-name="$route.params.space" @handle-cancel="toggleShowDelWorkspaceModal" @handle-delete="deleteSpace" />
		</NcModal>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import SelectUsers from './SelectUsers.vue'
import RemoveSpace from './RemoveSpace.vue'
import UserTable from './UserTable.vue'
import { destroy, rename, checkGroupfolderNameExist } from './services/groupfoldersService.js'
import showNotificationError from './services/Notifications/NotificationError.js'

export default {
	name: 'SpaceDetails',
	components: {
		NcActions,
		NcActionButton,
		NcActionInput,
		NcColorPicker,
		NcModal,
		NcMultiselect,
		SelectUsers,
		RemoveSpace,
		UserTable,
	},
	data() {
		return {
			createGroup: false, // true to display 'Create Group' ActionInput
			renameSpace: false, // true to display 'Rename space' ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
			showDelWorkspaceModal: false,
			isESR: false,
		}
	},
	computed: {
		// The title to display at the top of the page
		title() {
			return this.$route.params.space + ' [ID: ' + this.$store.state.spaces[this.$route.params.space].id + ']'
		},
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
			const space = this.$route.params.space
			destroy(this.$store.state.spaces[space])
				.then(resp => {
					if (resp.http.statuscode === 200) {
						this.$store.dispatch('removeSpace', {
							space: this.$store.state.spaces[space],
						})
						this.$router.push({
							path: '/',
						})
					}
				})
		},
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleCreateGroup()

			// Don't accept empty names
			const gid = e.target[0].value
			if (!gid) {
				return
			}

			// Creates group
			this.$store.dispatch('createGroup', { name: this.$route.params.space, gid })
		},
		async onSpaceRename(e) {
			// Hides ActionInput
			this.toggleRenameSpace()
      if (!e.target[0].value) {
        showNotificationError('Error to rename space', 'The name space must be defined.', 3000)
        return
			}

			const newSpaceName = e.target[0].value

			await checkGroupfolderNameExist(newSpaceName)

			// TODO: Change : the key from $root.spaces, groupnames, change the route into new spacename because
			// the path is `https://instance-nc/apps/workspace/workspace/Aang`
			const oldSpaceName = this.$route.params.space
			let responseRename = await rename(this.$store.state.spaces[oldSpaceName], newSpaceName, this)
			responseRename = responseRename.data

			if (responseRename.statuscode === 204) {
				const space = { ...this.$store.state.spaces[oldSpaceName] }
				space.name = responseRename.space
				space.groups = responseRename.groups
				this.$store.dispatch('updateSpace', {
					space,
				})
				this.$store.dispatch('removeSpace', {
					space: this.$store.state.spaces[oldSpaceName],
				})
				this.$router.push({
					path: `/workspace/${space.name}`,
				})
			}

			if (responseRename.statuscode === 401) {
				// TODO: May be to print an error message temporary
				console.error(responseRename.message)
			}

			if (responseRename.statuscode === 400) {
				this.$notify({
					title: t('workspace', 'Error to rename space'),
					text: t('workspace', 'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]'),
					type: 'error',
					duration: 6000,
				})
			}
		},
		// Sets a space's quota
		setSpaceQuota(quota) {
			if (quota === null) {
				return
			}
			const control = new RegExp(`^(${t('workspace', 'unlimited')}|\\d+(tb|gb|mb|kb)?)$`, 'i')
			if (!control.test(quota)) {
				this.$notify({
					title: t('workspace', 'Error'),
					text: t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota'),
					type: 'error',
				})
				return
			}
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
		updateColor(e) {
			const spacename = this.$route.params.space
			axios.post(generateUrl(`/apps/workspace/workspaces/${this.$store.state.spaces[spacename].id}/color`),
				{
					colorCode: e,
				})
				.then(resp => {
					this.$store.dispatch('updateColor', {
						name: spacename,
						colorCode: e,
					})
				})
				.catch(err => {
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured when trying to change the workspace\'s color.') + '<br>' + t('workspace', 'The error is: ') + err,
						type: 'error',
					})
				})
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

.space-color-picker {
	margin-right: 8px;
}

.space-name {
	margin-left: 8px;
	margin-top: -25px;
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
