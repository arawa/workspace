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
					<NcActions ref="ncAction" default-icon="icon-add">
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
							:show-trailing-button="true"
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
			<AddUsersTabs @close-sidebar="toggleShowSelectUsersModal" />
		</NcModal>
		<NcModal v-if="showDelWorkspaceModal"
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
import RemoveSpace from './RemoveSpace.vue'
import UserTable from './UserTable.vue'
import { renameSpace, removeWorkspace } from './services/spaceService.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import AddUsersTabs from './AddUsersTabs.vue'

export default {
	name: 'SpaceDetails',
	components: {
		AddUsersTabs,
		NcActions,
		NcActionButton,
		NcActionInput,
		NcColorPicker,
		NcModal,
		NcMultiselect,
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
			return this.$route.params.space
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
		async onSpaceRename(e) {
			// Hides ActionInput
			this.toggleRenameSpace()
			if (!e.target[0].value) {
				showNotificationError('Error to rename space', 'The name space must be defined.', 3000)
				return
			}

			const newSpaceName = e.target[0].value

			// TODO: Change : the key from $root.spaces, groupnames, change the route into new spacename because
			// the path is `https://instance-nc/apps/workspace/workspace/Aang`
			const oldSpaceName = this.$route.params.space
			let responseRename = await renameSpace(this.$store.state.spaces[oldSpaceName].id, newSpaceName)
			responseRename = responseRename.data

			if (responseRename.statuscode === 204) {
				const space = { ...this.$store.state.spaces[oldSpaceName] }
				space.name = responseRename.space

				this.$store.dispatch('updateSpace', {
					space,
				})
				this.$store.dispatch('removeSpace', {
					space: this.$store.state.spaces[oldSpaceName],
				})

				const groupKeys = Object.keys(space.groups)
				groupKeys.forEach(key => {
					const group = space.groups[key]
					/**
					 * To fix a bug where the space is renamed to single
					 * then to plural (or inversely)
					 * This bug is present from release 3.0.2
					 */
					if (!this.checkSpaceNameIsEqual(group.displayName, oldSpaceName)) {
						group.displayName = this.replaceSpaceName(group.displayName, oldSpaceName)
					}
					const newDisplayName = group.displayName.replace(oldSpaceName, newSpaceName)

					// Renames group
					this.$store.dispatch('renameGroup', {
						name: newSpaceName,
						gid: group.gid,
						newGroupName: newDisplayName,
					})
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
				const text = t('workspace', 'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]')
				showNotificationError('Error to rename space', text, 5000)
			}
		},
		/**
		 * @param {string} groupname the displayname from a group
		 * @param {string} oldSpaceName the currently space name
		 * To fix a bug from release 3.0.2
		 */
		checkSpaceNameIsEqual(groupname, oldSpaceName) {
			let spaceNameFiltered = ''

			if (groupname.startsWith('U-')) {
				spaceNameFiltered = groupname.replace('U-', '')
			}

			if (groupname.startsWith('WM-')) {
				spaceNameFiltered = groupname.replace('WM-', '')
			} else if (groupname.startsWith('GE-')) {
				spaceNameFiltered = groupname.replace('GE-', '')
			}

			if (groupname.startsWith('G-')) {
				spaceNameFiltered = groupname.replace('G-', '')
			}

			if (spaceNameFiltered === oldSpaceName) {
				return true
			}

			return false
		},
		/**
		 * @param {string} groupname the displayname from a group
		 * @param {string} oldSpaceName the currently space name
		 * To fix a bug from release 3.0.2
		 */
		replaceSpaceName(groupname, oldSpaceName) {
			const spaceNameSplitted = groupname
				.split('-')
				.filter(element => element)

			if (spaceNameSplitted[0] === 'WM'
					|| spaceNameSplitted[0] === 'U') {
				spaceNameSplitted[1] = oldSpaceName
			}

			if (spaceNameSplitted[0] === 'G') {
				const lengthMax = spaceNameSplitted.length - 1
				spaceNameSplitted[lengthMax] = oldSpaceName
			}

			return spaceNameSplitted.join('-')
		},
		// Sets a space's quota
		setSpaceQuota(quota) {
			if (quota === null) {
				return
			}
			const control = new RegExp(`^(${t('workspace', 'unlimited')}|\\d+(tb|gb|mb|kb)?)$`, 'i')
			if (!control.test(quota)) {
				const text = t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota')
				showNotificationError('Error', text, 3000)
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
					const text = t('workspace', 'A network error occured when trying to change the workspace\'s color.<br>The error is: {error}', { error: err })
					showNotificationError('Network error', text, 3000)
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
