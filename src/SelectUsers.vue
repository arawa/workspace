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
	<div class="select-users-wrapper">
		<div class="header-modal">
			<h1 class="title-add-users-modal">
				{{ t('workspace', 'Add users') }}
			</h1>
		</div>
		<NcMultiselect class="select-users-input"
			label="name"
			:custom-label="displayForSearching"
			track-by="uid"
			:loading="isLookingUpUsers"
			:multiple="false"
			:options="selectableUsers"
			:placeholder="t('workspace', 'Start typing to lookup users')"
			:tag-width="50"
			:user-select="true"
			@change="addUserToBatch"
			@close="selectableUsers=[]"
			@search-change="lookupUsers">
			<span slot="noOptions">{{ t('workspace', 'No username matches your current entry.') }}</span>
		</NcMultiselect>
		<div class="select-users-list">
			<div v-if="allSelectedUsers.length === 0"
				class="select-users-list-empty">
				<span>
					{{ t('workspace', 'No users selected') }}
				</span>
			</div>
			<div v-else>
				<div v-for="user in allSelectedUsers"
					:key="user.name"
					class="user-entry"
					:class="$store.getters.isMember($route.params.space, user) || !$route.params.group ? '' : 'user-not-member'">
					<div>
						<div class="icon-member" :class="$store.getters.isMember($route.params.space, user) ? 'is-member' : ''" />
						<NcAvatar :display-name="user.name" :user="user.uid" />
						<div class="user-name">
							<span> {{ user.name }} </span>
						</div>
					</div>
					<div class="user-entry-actions">
						<div v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)">
							<NcCheckboxRadioSwitch type="checkbox"
								class="role-toggle"
								:checked="user.role === 'admin'"
								@update:checked="toggleUserRole(user)">
								{{ t('workspace', 'S.A.') }}
							</NcCheckboxRadioSwitch>
						</div>
						<NcActions>
							<NcActionButton icon="icon-delete"
								@click="removeUserFromBatch(user)">
								{{ t('workspace', 'remove users from selection') }}
							</NcActionButton>
						</NcActions>
					</div>
				</div>
			</div>
		</div>
		<NcNoteCard v-if="$route.params.group && addingUsersToWorkspace"
			type="warning">
			<p>
				{{ t('workspace', 'Caution, users highlighted in red are not yet member of this workspace. They will be automaticaly added.') }}
			</p>
		</NcNoteCard>
		<div class="buttons-groups">
			<NcButton
				@click="addUsersToWorkspaceOrGroup()">
				{{ t('workspace', 'Add users') }}
			</button>
		</div>
		<!-- <div class="select-users-actions">
			<button class="icon-upload" @click="uploadNewFile()">
				<span>{{ t('workspace', 'Add users from csv file') }}</span>
			</button>
			<input ref="filesAttachment"
				type="file"
				hidden
				@change="handleUploadFile">
			<button class="icon-folder" style="padding: 8px 32px;" @click="shareCsvFromFiles()">
				<span>{{ t('workspace', 'Import csv from Files') }}</span>
			</button>
		</div> -->
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import Folder from 'vue-material-design-icons/Folder.vue'
import ManagerGroup from './services/Groups/ManagerGroup.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import Upload from 'vue-material-design-icons/Upload.vue'
import UserGroup from './services/Groups/UserGroup.js'

const picker = getFilePickerBuilder(t('deck', 'File to share'))
	.setMultiSelect(false)
	.setModal(true)
	.setType(1)
	.allowDirectories()
	.build()

export default {
	name: 'SelectUsers',
	components: {
		Folder,
		NcActionButton,
		NcNoteCard,
		NcActions,
		NcAvatar,
		NcButton,
		NcCheckboxRadioSwitch,
		NcMultiselect,
		Upload,
	},
	data() {
		return {
			allSelectedUsers: [], // All selected users from all searches
			isLookingUpUsers: false, // True when we are looking up users
			selectableUsers: [], // Users matching a search term
		}
	},
	computed: {
		// Returns true when at least 1 selected user is not yet member of the workspace
		addingUsersToWorkspace() {
			return !this.allSelectedUsers.every(user => {
				return this.$store.getters.isMember(this.$route.params.space, user)
			})
		},
	},
	methods: {
		// Adds users to workspace/group and close dialog
		// In the end, it always boils down to adding the user to a group
		// NOTE that the backend takes care of adding the user to the U- group, and Workspace managers
		// group if needed.
		// CAUTION, we are not giving a gid here but rather a group's displayName
		// (the space's name, in this.$route.params.space can change).
		// This should however be handled in the backend
		// IMPROVEMENT POSSIBLE: I think the backend nows store the real GID of
		// the U- and GE- groups in some specific attribute of the space object.
		// We might use them here.
		addUsersToWorkspaceOrGroup() {
			this.$emit('close')
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
					if (user.role === 'admin') {
						this.$store.dispatch('addUserToGroup', {
							name: this.$route.params.space,
							gid: ManagerGroup.getGid(space),
							user,
						})
					}
				} else {
					// Adding a user to the workspace
					if (user.role === 'admin') {
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
		displayForSearching({ name, email, uid }) {
			return `${name} - ${email} - ${uid}`
		},
		// Adds user to the batch when user selects user in the MultiSelect
		addUserToBatch(user) {
			this.allSelectedUsers.push(user)
		},
		// Lookups users in NC directory when user types text in the MultiSelect
		lookupUsers(term) {
			// safeguard for initialisation
			if (term === undefined || term === '') {
				return
			}
			const space = this.$store.state.spaces[this.$route.params.space]
			const spaceId = space.id
			// TODO: limit max results?
			this.isLookingUpUsers = true
			axios.post(generateUrl(`/apps/workspace/api/autoComplete/${term}/${spaceId}`),
				{
					space,
				})
				.then((resp) => {
					if (resp.status === 200) {
						const usersToDisplay = this.filterAlreadyPresentUsers(resp.data)
						this.selectableUsers = this.addSubtitleToUsers(usersToDisplay)
					} else {
						const text = t('workspace', 'An error occured while trying to lookup users.<br>The error is: {error}', { error: resp.statusText })
						showNotificationError('Error', text, 3000)
					}
				})
				.catch((e) => {
					const text = t('workspace', 'A network error occured while trying to lookup users.<br>The error is: {error}', { error: e })
					showNotificationError('Network error', text, 3000)
					console.error('Problem to search users', e)
				})
			this.isLookingUpUsers = false
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
					u.role = u.role === 'user' ? 'admin' : 'user'
					return u
				} else {
					return u
				}
			})
		},
		// When adding users to a space, show only those users who are not already member of the space
		filterAlreadyPresentUsers(recvUsers) {
			let users = []
			if (this.$route.params.group === undefined) {
				const space = this.$store.state.spaces[this.$route.params.space]
				users = recvUsers.filter(user => {
					return (!(user.uid in space.users))
				}, space)
			} else {
				users = recvUsers.filter(user => {
					return (!(user.groups.includes(this.$route.params.group)))
				})
			}
			// Filters user that are already selected
			return users.filter(newUser => {
				return this.allSelectedUsers.every(user => {
					return newUser.uid !== user.uid
				})
			})
		},
		addSubtitleToUsers(users) {
			return users.map(user => {
				return {
					...user,
					subtitle: user.subtitle ?? '',
				}
			})
		},
		async handleUploadFile(event) {
			if (event.target.files[0]) {
				this.isLookingUpUsers = true
				const bodyFormData = new FormData()
				const file = event.target.files[0]
				const space = this.$store.state.spaces[this.$route.params.space]
				const spaceObj = JSON.stringify(space)
				bodyFormData.append('file', file)
				bodyFormData.append('space', spaceObj)
				try {
					const users = await this.$store.dispatch('addUsersFromCSV', {
						formData: bodyFormData,
					})
					let usersToDisplay = this.filterAlreadyPresentUsers(users)
					usersToDisplay = this.addSubtitleToUsers(usersToDisplay)
					this.allSelectedUsers = [...this.allSelectedUsers, ...usersToDisplay]
				} catch (err) {
					if (err.response.data.length === 1) {
						const text = t('workspace', err.response.data[0])
						showNotificationError('Error', text, 3000)
					} else {
						const values = err.response.data[1].join()
						const text = t('workspace', `${err.response.data[0]} {values}`, { values })
						showNotificationError('Error', text, 5000)
					}
				}
				this.isLookingUpUsers = false
				event.target.value = ''
			}
		},
		uploadNewFile() {
			this.$refs.filesAttachment.click()
		},
		shareCsvFromFiles() {
			picker.pick()
				.then(async (path, title) => {
					console.debug(`path ${path} selected for sharing, title ${title}`)
					const space = this.$store.state.spaces[this.$route.params.space]
					const spaceString = JSON.stringify(space)
					const bodyFormData = new FormData()
					bodyFormData.append('path', path)
					bodyFormData.append('space', spaceString)
					try {
						const users = await this.$store.dispatch('importCsvFromFiles', { formData: bodyFormData })
						let usersToDisplay = this.filterAlreadyPresentUsers(users)
						usersToDisplay = this.addSubtitleToUsers(usersToDisplay)
						this.allSelectedUsers = [...this.allSelectedUsers, ...usersToDisplay]
					} catch (err) {
						if (err.response.data.length === 1) {
							const text = t('workspace', err.response.data[0])
							showNotificationError('Error', text, 3000)
						} else {
							const values = err.response.data[1].join()
							const text = t('workspace', `${err.response.data[0]} {values}`, { values })
							showNotificationError('Error', text, 5000)
						}
					}
				})
		},
	},
}
</script>

<style>
.icon-member {
	position: relative;
	left: 10px;
	top: -10px;
	z-index: 10;
	width: 20px;
	height: 20px;
}

.is-member {
	background-image: url('../img/member.png');
	background-repeat: no-repeat;
	background-position: center center;
	background-size: contain;
}

.modal-container {
	display: flex !important;
	height: 660px !important;
}

.multiselect__tags {
	border-color: #dbdbdb !important;
	margin-bottom: 5px;
}

.select-users-actions {
	display: flex;
	margin-top: 10px;
	width: 93%;
	justify-content: space-around;
}
.select-users-actions button {
	display: flex;
	flex-direction: column;
	background-position: 10px center;
}
.select-users-actions button, .add-users-wrapper button {
	width: fit-content;
}

.header-modal {
	display: flex;
	margin: 30px 0;
	margin-left: 60px;
	align-self: start;
}

.title-add-users-modal {
	font-weight: bold;
	font-size: 18px;
}

.select-users-input {
	width: 80%;
}

.select-users-list {
	flex-grow: 1;
	margin: 25px 0;
	border-style: solid;
	border-width: 1px;
	border-color: transparent;
	width: 82%;
	overflow: scroll;
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
	margin: 10px;
	max-width: 600px;
}

.user-entry {
	justify-content: space-between;
	padding-left: 5px;
}

.user-entry,
.user-entry div {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.user-name {
	margin-left: 10px;
	max-width: 440px;
}

.buttons-groups {
	display: flex;
	margin: 30px 0;
}

.user-not-member {
	background-color: rgba(var(--color-error-rgb), 0.5);
}

.role-toggle {
	cursor: pointer !important;
}

.icon-upload {
	background-position: 16px center;
	text-align: left;
}
.icon-upload span {
	padding-left: 28px;
}
</style>
