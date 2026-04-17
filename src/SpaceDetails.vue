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
	<div v-if="!$store.state.loadingUsersWaiting">
		<div class="header">
			<div class="space-name">
				<div class="space-color-picker color-dot"
					:style="{backgroundColor: $store.getters.getSpaceByNameOrId($route.params.space).color}" />
				<span class="titles-for-space">
					{{ title }}
				</span>
				<NcPopover placement="right"
					:triggers="['hover']">
					<template #trigger="{ attrs }">
						<NcCounterBubble v-bind="attrs"
							:class="isESR ? 'quota-bubble-esr' : 'quota-bubble'"
							type="outlined"
							:raw="true"
							:count="getQuota" />
					</template>
					<template #default>
						<div class="popover-content">
							{{ getQuotaHover }}
						</div>
					</template>
				</NcPopover>
			</div>
			<div v-if="!$store.getters.getSpaceByNameOrId($route.params.space).currentUserIsSimpleUser"
				class="space-actions">
				<NcActions ref="ncAction" default-icon="icon-add">
					<NcActionButton icon="icon-user"
						:close-after-click="true"
						@click="toggleShowSelectUsersModal">
						{{ t('workspace', 'Add users') }}
					</NcActionButton>
					<NcActionButton v-show="!createGroup"
						@click="toggleCreateGroup">
						<template #icon>
							<NcIconSvgWrapper name="icon-group" :path="mdiAccountMultiple" />
						</template>
						{{ t('workspace', 'Create a workspace group') }}
					</NcActionButton>
					<NcActionInput v-show="createGroup"
						ref="createGroupInput"
						:close-after-click="true"
						:show-trailing-button="true"
						@submit="onNewGroup">
						<template #icon>
							<NcIconSvgWrapper name="icon-group" :path="mdiAccountMultiple" />
						</template>
						{{ t('workspace', 'Group name') }}
					</NcActionInput>
					<NcActionButton v-if="$root.$data.addedGroupDisabled === false"
						:close-after-click="true"
						@click="toggleShowConnectedGroups">
						<template #icon>
							<NcIconSvgWrapper v-if="isDarkTheme"
								:size="16"
								:svg="AddedGroupWhite" />
							<NcIconSvgWrapper v-else
								:size="16"
								:svg="AddedGroupBlack" />
						</template>
						{{ t('workspace', 'Add a group') }}
					</NcActionButton>
				</NcActions>
				<NcActions>
					<NcActionButton v-if="$root.$data.isUserGeneralAdmin"
						icon="icon-rename"
						@click="openEditWorkspaceModal">
						{{ t('workspace', 'Edit the workspace') }}
					</NcActionButton>
				</NcActions>
				<NcActions v-if="$root.$data.isUserGeneralAdmin">
					<NcActionButton icon="icon-delete"
						:close-after-click="true"
						@click="toggleShowDelWorkspaceModal">
						{{ t('workspace', 'Delete space') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>
		<NcEmptyContent v-if="$store.state.noUsers"
			class="empty-content"
			:name="t('workspace', 'No users')">
			<template #icon>
				<NcIconSvgWrapper name="account-off" :path="mdiAccountOff" />
			</template>
			<template #description>
				{{ t('workspace', 'There are no users in this space/group yet') }}
			</template>
		</NcEmptyContent>
		<UserTable v-else
			:key="'user-' + $route.params.space"
			:space-name="$route.params.space" />
		<NcDialog v-if="showSelectUsersModal"
			:name="t('workspace', 'Add users')"
			size="normal"
			:open="showSelectUsersModal"
			@update:open="val => { showSelectUsersModal = val }">
			<AddUsersTabs @close-sidebar="toggleShowSelectUsersModal" />
		</NcDialog>
		<SelectConnectedGroups v-if="showSelectConnectedGroups"
			:space="space"
			@close="toggleShowConnectedGroups" />
		<RemoveSpace v-if="showDelWorkspaceModal"
			:space-name="title"
			@close="toggleShowDelWorkspaceModal"
			@handle-cancel="toggleShowDelWorkspaceModal"
			@handle-delete="deleteSpace" />
		<EditWorkspace v-if="showEditWorkspaceModal"
			:space="$store.getters.getSpaceByNameOrId($route.params.space)"
			:title="t('workspace', 'Edit the workspace')"
			:place-holder-workspace="t('workspace', 'Rename your workspace')"
			:button-name="t('workspace', 'Save')"
			:progress-bar="true"
			@click-action="save"
			@close="closeEditWorkspaceModal" />
	</div>
</template>

<script>
import EditWorkspace from './components/Modals/EditWorkspace.vue'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import SelectConnectedGroups from './SelectConnectedGroups.vue'
import RemoveSpace from './RemoveSpace.vue'
import UserTable from './UserTable.vue'
import { removeWorkspace, renameSpace } from './services/spaceService.js'
import AddUsersTabs from './AddUsersTabs.vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { mdiAccountOff, mdiAccountMultiple } from '@mdi/js'
import AddedGroupBlack from '../img/added_group_black.svg?raw'
import AddedGroupWhite from '../img/added_group_white.svg?raw'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import showNotificationError from './services/Notifications/NotificationError.js'

export default {
	name: 'SpaceDetails',
	components: {
		AddUsersTabs,
		EditWorkspace,
		NcActions,
		NcCounterBubble,
		NcEmptyContent,
		NcActionButton,
		NcActionInput,
		NcDialog,
		SelectConnectedGroups,
		RemoveSpace,
		UserTable,
		NcIconSvgWrapper,
		NcPopover,
	},
	setup() {
		return {
			isDarkTheme: useIsDarkTheme(),
		}
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
			space: undefined,
			mdiAccountMultiple,
			mdiAccountOff,
			AddedGroupBlack,
			AddedGroupWhite,
			iconUrl: undefined,
			// isDarkTheme: useIsDarkTheme(),
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
		getQuotaHover() {
			return 'Quota : ' + this.getQuota
		},
	},
	beforeMount() {
		this.space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
		if (Object.keys(this.space.users).length === 0) {
			this.$store.dispatch('loadUsers', { space: this.space })
		} else {
			this.$store.dispatch('setNoUsers', { activated: false })
		}

		if (this.space.managers === null) {
			this.$store.dispatch('loadAdmins', { space: this.space })
		}
	},
	beforeUpdate() {
		this.space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
		if (Object.keys(this.space.users).length === 0) {
			this.$store.dispatch('loadUsers', { space: this.space })
		} else {
			this.$store.dispatch('setNoUsers', { activated: false })
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
						this.$store.dispatch('decrementCountWorkspaces')
						this.$store.dispatch('decrementCountTotalWorkspaces')
						this.$store.dispatch('decrementCountTotalWorkspacesByQuery')
						this.$router.push({ name: 'space.table' })
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
		openEditWorkspaceModal() {
			this.showEditWorkspaceModal = true
		},
		closeEditWorkspaceModal() {
			this.showEditWorkspaceModal = false
		},
		async save(payload) {
			const oldSpace = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const oldSpaceName = oldSpace.name
			const space = { ...oldSpace }

			if (payload.colorCode !== space.color) {
				axios.post(generateUrl(`/apps/workspace/workspaces/${oldSpace.id}/color`),
					{
						colorCode: payload.colorCode,
					})
					.then(resp => {
						this.$store.dispatch('updateColor', {
							name: oldSpaceName,
							colorCode: payload.colorCode,
						})
					})
					.catch(err => {
						const text = t('workspace', 'A network error occurred when trying to change the workspace\'s color.<br>Error: {error}', { error: err })
						showNotificationError(t('workspace', 'Network error'), text, 3000)
					})
			}

			if ((oldSpaceName !== payload.name) && (payload.name !== '')) {
				let responseRename = await renameSpace(oldSpace.id, payload.name)
				responseRename = responseRename.data

				if (responseRename.statuscode === 204) {
					const spaceBeforeRenamed = { ...oldSpace }
					spaceBeforeRenamed.name = responseRename.space
					space.name = responseRename.space

					this.$store.dispatch('updateSpace', {
						space: spaceBeforeRenamed,
					})
					this.$store.dispatch('removeSpace', {
						space: oldSpace,
					})

					const groupKeys = Object.keys(spaceBeforeRenamed.groups)
					groupKeys.forEach(key => {
						const group = spaceBeforeRenamed.groups[key]
						if (!this.checkSpaceNameIsEqual(group.displayName, oldSpaceName)) {
							group.displayName = this.replaceSpaceName(group.displayName, oldSpaceName)
						}
						const newDisplayName = group.displayName.replace(oldSpaceName, payload.name)
						// Renames group
						this.$store.dispatch('renameGroup', {
							name: payload.name,
							gid: group.gid,
							newGroupName: newDisplayName,
						})
					})
				}
			}

			if (space.quota !== payload.quota) {
				this.$store.dispatch('setSpaceQuota', {
					name: space.name,
					quota: payload.quota,
				})
			}

			this.closeEditWorkspaceModal()

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
			const spaceNameSplit = groupname
				.split('-')
				.filter(element => element)

			if (spaceNameSplit[0] === 'WM'
					|| spaceNameSplit[0] === 'U') {
				spaceNameSplit[1] = oldSpaceName
			}

			if (spaceNameSplit[0] === 'G') {
				const lengthMax = spaceNameSplit.length - 1
				spaceNameSplit[lengthMax] = oldSpaceName
			}

			return spaceNameSplit.join('-')
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

@media only screen and (max-width: 900px) {
	.dialog-addusers-tabs .modal-wrapper .modal-container {
		max-height: 500px !important;
	}
}

.color-picker {
	margin: 0px;
}

.quota-bubble {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
	font-size: var(--default-font-size) !important;
	line-height: 32px !important;
	padding: 0 12px !important;
}

.quota-bubble-esr {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100% !important;
	font-size: var(--default-font-size) !important;
	line-height: 32px !important;
	padding: 0 12px !important;
}

.space-name {
    width: 100%;
    flex-grow: 1;
    display: inline-flex;
    align-items: center;
}

.user-actions {
	flex-flow: row-reverse;
}
.modal-wrapper--small .modal-container {
	min-height: 12rem !important;
}

.popover-content {
	padding: 8px;
}
</style>
