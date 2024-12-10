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
	<NcModal class="modal-select-users"
		@close="close()">
		<div class="select-users-wrapper">
			<header class="header-modal">
				<h1 class="title-add-users-modal">
					{{ t('workspace', 'Add users') }}
				</h1>
			</header>
			<div class="body-select-users">
				<NcSelect class="searchbar-users"
					label="name"
					track-by="uid"
					:custom-label="displayForSearching"
					:loading="isLookingUpUsers"
					:multiple="false"
					:options="selectableUsers"
					:placeholder="t('workspace', 'Start typing to lookup users')"
					:appendToBody="false"
					:userSelect="true"
					:tag-width="50"
					:user-select="true"
					@option:selected="addUserToBatch"
					@search="lookupUsers"
					@close="selectableUsers=[]" />
			</div>
			<div class="content-users-list">
				<div v-if="allSelectedUsers.length !== 0"
					class="select-user-list">
					<div v-for="user in allSelectedUsers"
						:key="user.name"
						class="user-item"
						:class="$store.getters.isMember($route.params.space, user) || !$route.params.slug ? '' : 'user-not-member'">
						<div>
							<div class="icon-member" :class="$store.getters.isMember($route.params.space, user) ? 'is-member' : ''" />
							<NcAvatar :display-name="user.name" :user="user.uid" />
							<div class="username">
								<span>{{ user.name }}</span>
							</div>
						</div>
						<div>
							<NcCheckboxRadioSwitch v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.slug)"
								class="role-toggle"
								type="checkbox"
								:checked="user.role === 'admin'"
								:disabled="$store.getters.isMember($route.params.space, user)"
								@update:checked="toggleUserRole(user)">
								{{ t('workspace', 'S.A.') }}
							</NcCheckboxRadioSwitch>
							<NcActions>
								<NcActionButton icon="icon-delete"
									@click="removeUserFromBatch(user)">
									{{ t('workspace', 'remove users from selection') }}
								</NcActionButton>
							</NcActions>
						</div>
					</div>
				</div>
				<NcEmptyContent v-else
					class="content-user-list-empty"
					:title="t('workspace', 'No users selected')" />
			</div>
			<NcNoteCard v-if="$route.params.slug && addingUsersToWorkspace"
				class="note-card"
				type="warning">
				<p>
					{{ t('workspace', 'Caution, users highlighted in red are not yet member of this workspace. They will be automaticaly added.') }}
				</p>
			</NcNoteCard>
			<NcButton type="secondary"
				class="btn-add-users"
				@click="addUsersToWorkspaceOrGroup()">
					{{ t('workspace', 'Add users') }}
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import ManagerGroup from './services/Groups/ManagerGroup.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import UserGroup from './services/Groups/UserGroup.js'
import { generateUrl } from '@nextcloud/router'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import { getFilePickerBuilder } from '@nextcloud/dialogs'

const picker = getFilePickerBuilder(t('deck', 'File to share'))
	.setMultiSelect(false)
	.setModal(true)
	.setType(1)
	.allowDirectories()
	.build()

export default {
	name: 'SelectUsers',
	components: {
		NcAvatar,
		NcActions,
		NcActionButton,
		NcCheckboxRadioSwitch,
		NcButton,
		NcNoteCard,
		NcEmptyContent,
		NcModal,
		NcSelect,
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
				if (this.$route.params.slug !== undefined) {
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
				} else {
					this.addUserFromWorkspace(user, space)
				}
			})
		},
		addUserFromWorkspace(user, space) {
			let gid = ''
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
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: this.$route.params.space,
			})
			if (user.role === 'admin') {
				this.$store.dispatch('incrementGroupUserCount', {
					spaceName: this.$route.params.space,
					gid: UserGroup.getGid(space)
				})
			}
		},
		addExistingUserFromSubgroup(user) {
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug))
			})
			this.$store.dispatch('addUserToGroup', {
				name: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
		},
		addNewUserFromSubgroup(user, space) {
			this.$store.dispatch('addUserToGroup', {
				name: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug))
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: this.$route.params.space,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: UserGroup.getGid(space)
			})
			if (user.role === 'admin') {
				this.$store.dispatch('addUserToGroup', {
					name: this.$route.params.space,
					gid: ManagerGroup.getGid(space),
					user,
				})
				this.$store.dispatch('incrementGroupUserCount', {
					spaceName: this.$route.params.space,
					gid: ManagerGroup.getGid(space)
				})
			}
		},
		addUserFromManagerGroup(user, space) {
			const usersBackup = [...Object.keys(space.users)]
			this.$store.dispatch('addUserToGroup', {
				name: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug))
			})
			if (usersBackup.includes(user.uid)) {
				return
			}
			this.$store.dispatch('addUserToGroup', {
				name: this.$route.params.space,
				gid: UserGroup.getGid(space),
				user,
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: this.$route.params.space,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: UserGroup.getGid(space)
			})
		},
		addUserFromUserGroup(user) {
			this.$store.dispatch('addUserToGroup', {
				name: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug)),
				user,
			})
			this.$store.dispatch('incrementGroupUserCount', {
				spaceName: this.$route.params.space,
				gid: decodeURIComponent(decodeURIComponent(this.$route.params.slug))
			})
			this.$store.dispatch('incrementSpaceUserCount', {
				spaceName: this.$route.params.space,
			})
		},
		close() {
			this.$emit('close')
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
			if (this.$route.params.slug === undefined) {
				const space = this.$store.state.spaces[this.$route.params.space]
				users = recvUsers.filter(user => {
					return (!(user.uid in space.users))
				}, space)
			} else {
				users = recvUsers.filter(user => {
					return (!(user.groups.includes(decodeURIComponent(this.$route.params.slug))))
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

<style scoped>
.icon-member {
	position: relative;
	left: 10px;
	top: -10px;
	z-index: 10;
	width: 20px;
	height: 20px;
}

.modal-select-users :deep(.modal-wrapper .modal-container) {
	min-height: auto;
}

.body-select-users {
	display: flex;
}

.is-member {
	background-image: url('../img/member.png');
	background-repeat: no-repeat;
	background-position: center center;
	background-size: contain;
}

.header-modal {
	display: flex;
	padding: 10px;
	font-weight: bold;
	align-self: start;
	margin-left: 16px;
}

.header-modal h1 {
	margin: 10px;
	margin-bottom: 16px;
	font-size: 20px;
}

.searchbar-groups {
	width: 500px;
}

.searchbar-groups :deep(.vs__dropdown-toggle) {
	border: 2px solid var(--color-border-dark);
}

.body-select-users :deep(.v-select.select.vs--open .vs__dropdown-toggle) {
  border-width: 1px;
  border-color: var(--color-border-dark);
  border-bottom: rgb(0,0,0,0);
}

.body-select-users :deep(.v-select.select.vs--open .vs__dropdown-menu) {
  border-width: 1px !important;
  border-color: var(--color-border-dark) !important;
}

.body-select-users :deep(.v-select.select .vs__dropdown-toggle) {
  border-width: 1px;
  border-color: var(--color-border-dark);
}

.content-users-list {
	width: 90%;
	height: 400px;
	padding: 8px;
	margin-top: 16px;
}

.select-user-list {
	display: flex;
	flex-direction: column;
	overflow: scroll;
	height: 100%;
}

.select-users-wrapper {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
}

.user-item {
	display: flex;
	justify-content: space-between;
}

.user-item,
.user-item div {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.content-user-list-empty {
	width: 100%;
	height: 100%;
	margin: 0px 0px !important;
	justify-content: center;
}

.content-user-list-empty h2 {
	font-size: 26px;
}

.btn-add-users {
	margin: 24px 0 24px 0;
}

.searchbar-users {
	width: 500px;
}

.searchbar-users :deep(.vs__dropdown-toggle) {
	border: 2px solid var(--color-border-dark);
}

.username {
	margin-left: 14px;
}

.note-card {
	width: 500px;
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
