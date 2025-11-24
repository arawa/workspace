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
		<NcAppSidebar
			name="select users"
			class="my-sidebar"
			@update:active="toggleImportTab">
			<NcAppSidebarTab id="search"
				:name="titleSearch"
				:order="1">
				<MultiSelectUsers class="input-select-users"
					:all-selected-users="allSelectedUsers"
					@change="addUserToBatch" />
				<template #icon>
					<span />
				</template>
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
				<template #icon>
					<span />
				</template>
			</NcAppSidebarTab>
		</NcAppSidebar>
		<div class="information-import">
			<NcPopover>
				<template #trigger="{attr}">
					<InformationOutline v-bind="attr"
						class="information-image"
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
					:class="$store.getters.isMember($route.params.space, user) || !$route.params.slug ? '' : 'user-not-member'"
					:user="user"
					@toggle-role="toggleUserRole"
					@remove-user="removeUserFromBatch" />
			</div>
		</div>
		<NcNoteCard v-if="$route.params.slug && addingUsersToWorkspace"
			type="warning"
			class="note-card">
			<p>
				{{ t('workspace', 'Caution, users highlighted in red are not yet member of this workspace. They will be automatically added.') }}
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
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcRichText from '@nextcloud/vue/components/NcRichText'
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
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			this.allSelectedUsers.forEach(user => {
				if (this.$route.params.slug === undefined) {
					this.addUserFromWorkspace(user, space)
					return
				}

				if (decodeURIComponent(this.$route.params.slug).startsWith('SPACE-U')) {
					this.addUserFromUserGroup(user)
					return
				}

				if (decodeURIComponent(this.$route.params.slug).startsWith('SPACE-GE')) {
					this.addUserFromManagerGroup(user, space)
					return
				}

				if (Object.keys(space.users).includes(user.uid) && decodeURIComponent(this.$route.params.slug).startsWith('SPACE-G-')) {
					this.addExistingUserFromSubgroup(user)
				} else {
					this.addNewUserFromSubgroup(user, space)
				}
			})
			this.$store.dispatch('setNoUsers', { activated: false })
		},
		addUserFromWorkspace(user, space) {
			let gid = ''
			if (user.role === 'wm') {
				gid = ManagerGroup.getGid(space)
			} else {
				gid = UserGroup.getGid(space)
			}
			this.$store.dispatch('addUserToGroup', {
				name: space.name,
				gid,
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid,
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: space.name,
			})
			if (user.role === 'wm') {
				this.$store.dispatch('addUserManager', {
					spaceName: space.name,
					user,
				})
				this.$store.dispatch('incrementGroupUserCount', {
					spaceName: space.name,
					gid: UserGroup.getGid(space),
				})
			}
		},
		addExistingUserFromSubgroup(user) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const name = space.name
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
			})
			this.$store.dispatch('addUserToGroup', {
				name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			if (user.role === 'wm') {
				this.$store.dispatch('addUserToGroup', {
					name: space.name,
					gid: ManagerGroup.getGid(space),
					user,
				})
				this.$store.dispatch('incrementGroupUserCount', {
					spaceName: space.name,
					gid: ManagerGroup.getGid(space),
				})
			}
			if (user.is_connected) {
				this.$store.commit('TOGGLE_USER_CONNECTED', { name, user })
			}
		},
		addNewUserFromSubgroup(user, space) {
			this.$store.dispatch('addUserToGroup', {
				name: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: space.name,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid: UserGroup.getGid(space),
			})
			if (user.role === 'wm') {
				this.$store.dispatch('addUserToGroup', {
					name: space.name,
					gid: ManagerGroup.getGid(space),
					user,
				})
				this.$store.dispatch('incrementGroupUserCount', {
					spaceName: space.name,
					gid: ManagerGroup.getGid(space),
				})
				this.$store.dispatch('addUserManager', {
					spaceName: space.name,
					user,
				})
			}
		},
		addUserFromManagerGroup(user, space) {
			const usersBackup = [...Object.keys(space.users)]
			this.$store.dispatch('addUserToGroup', {
				name: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
			})
			if (usersBackup.includes(user.uid)) {
				return
			}
			this.$store.dispatch('addUserToGroup', {
				name: space.name,
				gid: UserGroup.getGid(space),
				user,
			})
			this.$store.dispatch('addUserManager', {
				spaceName: space.name,
				user,
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: space.name,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid: UserGroup.getGid(space),
			})
		},
		addUserFromUserGroup(user) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			this.$store.dispatch('addUserToGroup', {
				name: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: space.name,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: space.name,
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
	flex: 1 1 120px !important;
}

.select-users-wrapper :deep(header.app-sidebar-header) {
	display: none !important;
}

// Change the height of the modal container
// to make space for the NcNoteCard
.modal-container {
	max-height: 900px !important;
}

@media only screen and (max-height: 700px) {
	.select-users-list-empty {
		line-height: 150px !important;
	}

	.select-users-list {
		height: 160px !important;
		margin: 0 !important;
	}

	.note-card {
		font-size: 12px !important;
	}

}

// Fix : tabs implies a min-height of 256 that overlaps the user list
.select-users-wrapper :deep(.app-sidebar-tabs__content) {
	min-height: 60px !important;
	height: auto !important;
	padding-top: 20px;
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
	padding: 8px;
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
	top: 56px;
	right: 66px;
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
