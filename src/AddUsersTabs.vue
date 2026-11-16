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
		<div class="tabs">
			<!-- The arrows go from one tab to the other, as the ARIA tabs pattern expects -->
			<div class="tabs__list"
				role="tablist"
				@keydown.left.prevent="selectTab(!importTab)"
				@keydown.right.prevent="selectTab(!importTab)">
				<button id="add-users-tab-search"
					ref="searchTab"
					type="button"
					class="tabs__tab"
					role="tab"
					:tabindex="importTab ? -1 : 0"
					:aria-selected="!importTab"
					aria-controls="add-users-panel-search"
					@click="importTab = false">
					{{ titleSearch }}
				</button>
				<button id="add-users-tab-import"
					ref="importTab"
					type="button"
					class="tabs__tab"
					role="tab"
					:tabindex="importTab ? 0 : -1"
					:aria-selected="importTab"
					aria-controls="add-users-panel-import"
					@click="importTab = true">
					{{ titleImport }}
				</button>
			</div>
			<NcPopover>
				<template #trigger="{attr}">
					<InformationOutline v-bind="attr"
						class="information-image"
						:class="{ 'information-image--muted': !importTab }"
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
		<!-- v-show keeps the search and its results when going to the import and back -->
		<div v-show="!importTab"
			id="add-users-panel-search"
			class="tab-panel"
			role="tabpanel"
			aria-labelledby="add-users-tab-search">
			<MultiSelectUsers :all-selected-users="allSelectedUsers"
				@toggle="toggleUserInBatch">
				<SelectedUsersChips :users="allSelectedUsers"
					@toggle-role="toggleUserRole"
					@remove-user="removeUserFromBatch" />
			</MultiSelectUsers>
		</div>
		<div v-show="importTab"
			id="add-users-panel-import"
			class="tab-panel"
			role="tabpanel"
			aria-labelledby="add-users-tab-import">
			<div class="buttons-import-groups">
				<ButtonUploadLocalFile :all-selected-users="allSelectedUsers"
					@push="pushUsersFromButton" />
				<ButtonUploadShareFiles :all-selected-users="allSelectedUsers"
					@push="pushUsersFromButton" />
			</div>
			<SelectedUsersChips v-if="importTab"
				class="import-chips"
				:users="allSelectedUsers"
				@toggle-role="toggleUserRole"
				@remove-user="removeUserFromBatch" />
		</div>
		<NcNoteCard v-if="$route.params.slug && addingUsersToWorkspace"
			type="info"
			class="note-card">
			<p>
				{{ t('workspace', 'Users highlighted in red are not yet member of this workspace. They will be automatically added.') }}
			</p>
		</NcNoteCard>
		<div class="buttons-groups">
			<NcButton variant="primary"
				:disabled="allSelectedUsers.length === 0"
				@click="addUsersToWorkspaceOrGroup()">
				{{ t('workspace', 'Add these users') }}
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
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import SelectedUsersChips from './SelectedUsersChips.vue'
import UserGroup from './services/Groups/UserGroup.js'

export default {
	name: 'AddUsersTabs',
	components: {
		ButtonUploadLocalFile,
		ButtonUploadShareFiles,
		InformationOutline,
		MultiSelectUsers,
		NcButton,
		NcPopover,
		NcRichText,
		SelectedUsersChips,
		NcNoteCard,
	},
	data() {
		return {
			allSelectedUsers: [], // All selected users from all searches
			importTab: false,
		}
	},
	computed: {
		titleImport() {
			return t('workspace', 'Import a .csv file')
		},
		titleSearch() {
			return t('workspace', 'Search users')
		},
		informCsvStructureMessage() {
			return t('workspace', 'Your CSV file must follow this structure:')
		},
		csvTemplateMarkdown() {
			return `> **user, role**
				pierre.quiroul,user
				awang,wm
				radjiv.velasquez,u
				christine.dupont@nextcloud.fr,wm
				jdoe@nextcloud.com,user`
		},
		// Returns true when at least 1 selected user is not yet member of the workspace
		addingUsersToWorkspace() {
			return !this.allSelectedUsers.every(user => {
				return this.$store.getters.isMember(this.$route.params.space, user)
			})
		},
	},
	methods: {
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
		// Selects a user found by the search, or unselects them when already selected
		toggleUserInBatch(user) {
			if (!user.uid) {
				return
			}
			if (this.allSelectedUsers.some(u => u.uid === user.uid)) {
				this.removeUserFromBatch(user)
				return
			}
			this.allSelectedUsers.push(this.toBatchUser(user))
		},
		// A copy, so a role picked here never sticks to the search results,
		// with the role in lowercase as the add paths compare it strictly ('WM' in a CSV file)
		toBatchUser(user) {
			return { ...user, role: user.role?.toLowerCase() === 'wm' ? 'wm' : 'user' }
		},
		selectTab(importTab) {
			this.importTab = importTab
			this.$nextTick(() => this.$refs[importTab ? 'importTab' : 'searchTab'].focus())
		},
		// Removes a user from the batch
		removeUserFromBatch(user) {
			this.allSelectedUsers = this.allSelectedUsers.filter((u) => {
				return u.uid !== user.uid
			})
		},
		// Changes the role of a user
		toggleUserRole(user) {
			const selected = this.allSelectedUsers.find(u => u.uid === user.uid)
			selected.role = selected.role === 'wm' ? 'user' : 'wm'
		},
		pushUsersFromButton(element) {
			this.allSelectedUsers = element.map(this.toBatchUser)
		},
	},
}
</script>

<style lang="scss" scoped>

@media only screen and (max-height: 700px) {
	.note-card {
		font-size: 12px !important;
	}

}

.select-users-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	width: 100%;
	// A fixed height keeps the buttons in view, only the lists of users scroll
	height: min(700px, 75vh);
}

.buttons-import-groups {
	display: flex;
	justify-content: space-around;
}

.tabs {
	display: flex;
	align-items: center;
	gap: 8px;
	width: 90%;
	border-bottom: 1px solid var(--color-border);
}

.tabs__list {
	display: flex;
	flex: 1;
}

.tabs__tab {
	flex: 1;
	margin: 0;
	padding: 12px;
	border: 0;
	border-bottom: 3px solid transparent;
	border-radius: 0;
	background: none;
	color: var(--color-main-text);
	cursor: pointer;
}

.tabs__tab:hover {
	background-color: var(--color-background-hover);
}

.tabs__tab[aria-selected="true"] {
	border-bottom-color: var(--color-primary-element);
	font-weight: bold;
}

.tab-panel {
	display: flex;
	flex: 1 1 0;
	flex-direction: column;
	gap: 15px;
	min-height: 0;
	width: 90%;
	margin-top: 15px;
}

.import-chips {
	flex: 1 1 0;
	min-height: 0;
	overflow-y: auto;
}

.information-image {
	cursor: pointer;
}

.popover {
	padding: 15px;
}

.information-image--muted {
	color: grey;
}

.note-card {
	width: 500px;
}

.buttons-groups {
	display: flex;
	flex-shrink: 0;
	justify-content: flex-end;
	width: 90%;
	margin: 25px 0 25px 0;
}

</style>
