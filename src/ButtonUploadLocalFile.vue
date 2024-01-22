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
	<div>
		<NcButton @click="uploadNewFile()">
			<template #icon>
				<Upload :size="20" />
			</template>
			{{ t('workspace', 'Upload new files') }}
		</NcButton>
		<input ref="filesAttachment"
			type="file"
			hidden
			@change="handleUploadFile">
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Upload from 'vue-material-design-icons/Upload.vue'
import showNotificationError from './services/Notifications/NotificationError.js'

export default {
	name: 'ButtonUploadLocalFile',
	components: {
		NcButton,
		Upload,
	},
	props: {
		allSelectedUsers: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			isLookingUpUsers: false, // True when we are looking up users
		}
	},
	methods: {
		uploadNewFile() {
			this.$refs.filesAttachment.click()
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
					this.$emit('push', [...this.allSelectedUsers, ...usersToDisplay])
					// this.allSelectedUsers = [...this.allSelectedUsers, ...usersToDisplay]
				} catch (err) {
					let duration = 5000

					// change the duration of the notification
					// related to the number of the word.
					if (err.response.data.data.message.split(' ').length >= 30) {
						duration = 8000
					}

					const title = err.response.data.data.title
					const text = err.response.data.data.message
					showNotificationError(title, text, duration)
					console.error(err.response.data.exception)
				}
				this.isLookingUpUsers = false
				event.target.value = ''
			}
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
	},
}
</script>
