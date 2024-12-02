<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2024 Baptiste Fotia <baptiste.fotia@arawa.fr>

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
	<div class="select-users-wrapper">
		<NcAppSidebar name="Ajouter des utilisateurs"
			class="my-sidebar"
			:title="title"
			@update:active="toggleImportTab"
			@close="closeSidebar">
			<NcAppSidebarTab id="search"
				:name="titleSearch"
				:order="1">
				<MultiSelectUsers class="input-select-users"
					:all-selected-users="allSelectedUsers"
					@change="addUserToBatch" />
			</NcAppSidebarTab>
			<NcAppSidebarTab id="import"
				:name="titleImport"
				:order="2">
				<div class="buttons-import-groups">
					<ButtonUploadLocalFile :all-selected-users="allSelectedUsers"
						@push="pushUsersFromButton" />
					<ButtonUploadShareFiles :all-selected-users="allSelectedUsers"
						@push="pushUsersFromButton" />
				</div>
			</NcAppSidebarTab>
		</NcAppSidebar>
		<div class="information-import">
			<NcPopover>
				<template #trigger>
					<InformationOutline class="information-image"
						:class="onImportTab"
						:size="17" />
				</template>
				<div class="popover">
					<p>{{ informCsvStructureMessage }}</p>
					<br>
					<NcRichText :use-markdown="true"
						:text="csvTemplateMarkdown" />
				</div>
			</NcPopover>
		</div>
		<div class="select-users-list">
			<div v-if="allSelectedUsers.length === 0"
				class="select-users-list-empty">
				<span>
					{{ t('workspace', 'No users selected') }}
				</span>
			</div>
			<div v-else>
				<UserCard v-for="user in allSelectedUsers"
					:key="user.name"
					class="user-entry"
					:class="$store.getters.isMember($route.params.space, user) || !$route.params.group ? '' : 'user-not-member'"
					:user="user"
					@toggle-role="toggleUserRole"
					@remove-user="removeUserFromBatch" />
			</div>
		</div>
		<NcNoteCard v-if="$route.params.group && addingUsersToWorkspace"
			type="warning"
			class="note-card">
			<p>
				{{ t('workspace', 'Caution, users highlighted in red are not yet member of this workspace. They will be automaticaly added.') }}
			</p>
		</NcNoteCard>
		<div class="buttons-groups">
			<NcButton
				@click="addUsersToWorkspaceOrGroup()">
				{{ t('workspace', 'Add') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import ButtonUploadLocalFile from './ButtonUploadLocalFile.vue'
import ButtonUploadShareFiles from './ButtonUploadShareFiles.vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import ManagerGroup from './services/Groups/ManagerGroup.js'
import MultiSelectUsers from './MultiSelectUsers.vue'
import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar.js'
import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcPopover from '@nextcloud/vue/dist/Components/NcPopover.js'
import NcRichText from '@nextcloud/vue/dist/Components/NcRichText.js'
import UserCard from './UserCard.vue'
import UserGroup from './services/Groups/UserGroup.js'

export default {
	name: 'AddUsersTabs',
	components: {
		ButtonUploadLocalFile,
		ButtonUploadShareFiles,
		InformationOutline,
		MultiSelectUsers,
		NcAppSidebar,
		NcAppSidebarTab,
		NcButton,
		NcPopover,
		NcRichText,
		UserCard,
		NcNoteCard,
	},
	data() {
		return {
			allSelectedUsers: [], // All selected users from all searches
			importTab: false,
		}
	},
	computed: {
		title() {
			return t('workspace', 'Add users')
		},
		titleImport() {
			return t('workspace', 'Import a .csv file')
		},
		titleSearch() {
			return t('workspace', 'Search users')
		},
		informCsvStructureMessage() {
			return t('workspace', 'You csv file must follow this structure:')
		},
		csvTemplateMarkdown() {
			return `> **user, role**
				pierre.quiroul,user
				awang,wm
				radjiv.velasquez,u
				christine.dupont@nextcloud.fr,wm
				jdoe@nextcloud.com,user`
		},
		onImportTab() {
			let cssClass = 'onImportTab'

			if (this.importTab) {
				cssClass = ''
			}
			return cssClass
		},
		// Returns true when at least 1 selected user is not yet member of the workspace
		addingUsersToWorkspace() {
			return !this.allSelectedUsers.every(user => {
				return this.$store.getters.isMember(this.$route.params.space, user)
			})
		},
	},
	methods: {
		closeSidebar() {
			this.$emit('close-sidebar')
		},
		addUsersToWorkspaceOrGroup() {
			this.$emit('close-sidebar')
			const space = this.$store.state.spaces[this.$route.params.space]
			this.allSelectedUsers.forEach(user => {
				let gid = ''
				if (this.$route.params.group !== undefined) {
					// Adding a user to a workspace 'subgroup
					this.$store.dispatch('addUserToGroup', {
						name: this.$route.params.space,
						gid: this.$route.params.group,
						user,
					})
					if (this.isWorkspaceManager(user.role)) {
						this.$store.dispatch('addUserToGroup', {
							name: this.$route.params.space,
							gid: ManagerGroup.getGid(space),
							user,
						})
					}
				} else {
					// Adding a user to the workspace
					if (this.isWorkspaceManager(user.role)) {
						gid = ManagerGroup.getGid(space)
					} else {
						gid = UserGroup.getGid(space)
					}
					this.$store.dispatch('addUserToGroup', {
						name: this.$route.params.space,
						gid,
						user,
					})
				}
			})
		},
		// Adds user to the batch when user selects user in the MultiSelect
		addUserToBatch(user) {
			this.allSelectedUsers.push(user)
		},
		isWorkspaceManager(role) {
			role = role.toLowerCase()
			return role === 'wm'
		},
		// Removes a user from the batch
		removeUserFromBatch(user) {
			this.allSelectedUsers = this.allSelectedUsers.filter((u) => {
				return u.name !== user.name
			})
		},
		// Changes the role of a user
		toggleUserRole(user) {
			this.allSelectedUsers = this.allSelectedUsers.map(u => {
				if (u.name === user.name) {
					u.role = u.role === 'user' ? 'wm' : 'user'
					return u
				} else {
					return u
				}
			})
		},
		toggleImportTab(active) {
			if (active === 'import') {
				this.importTab = true
			} else {
				this.importTab = false
			}
		},
		pushUsersFromButton(element) {
			this.allSelectedUsers = element
		},
	},
}
</script>

<style lang="scss" scoped>

section.app-sidebar__tab--active {
	min-height: auto !important;
	display: flex !important;
	flex-direction: column !important;
	height: 13% !important;
	justify-content: center !important;
	align-items: center !important;
	overflow: visible !important;
}

.select-users-wrapper :deep(.app-sidebar-tabs) {
	margin-top: -10px !important;
}

// Change the height of the modal container
// to make space for the NcNoteCard
.modal-container {
	max-height: 900px !important;
}

// FIXME: Obivously we should at some point not randomly reuse the sidebar component
// since this is not oficially supported
.modal-container .app-sidebar {
	$modal-padding: 14px;
	border: 0;
	min-width: calc(100% - #{$modal-padding * 2});
	position: relative;
	top: 0;
	left: 0;
	right: 0;
	max-width: calc(100% - #{$modal-padding * 2});
	padding: 0 14px;
	height: auto; // retirer le mode auto
	overflow: initial;
	user-select: text;
	-webkit-user-select: text;
}

.content-tab {
	width: 100%;
}

.select-users-wrapper {
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-items: center;
	width: 100%;
}

.select-users-list {
	flex-grow: 1;
	margin: 25px 0;
	border-style: solid;
	border-width: 1px;
	border-color: transparent;
	width: 82%;
	overflow: scroll;
	height: 330px;
}

.select-users-list-empty {
	text-align: center;
	line-height: 300px;
	width: 100%;
}

.select-users-wrapper {
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-items: center;
}

.user-entry {
	justify-content: space-between;
	padding-left: 5px;
}

.user-entry {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.user-not-member {
	background-color: rgba(var(--color-error-rgb), 0.5);
}

.buttons-import-groups {
	display: flex;
	width: 560px;
	justify-content: space-around;
	margin-top: 15px;
}

.input-select-users {
	display: flex;
	justify-content: center;
	width: 100%;
	margin-top: 15px;
}

.information-import {
	position: absolute;
	top: 108px;
	right: 54px;
	z-index: 9999;
}

.information-image {
	cursor: pointer;
}

.popover {
	padding: 15px;
}

.onImportTab {
	color: grey;
}

.note-card {
	width: 500px;
}

.buttons-groups {
	margin: 25px 0 25px 0;
}

</style>
