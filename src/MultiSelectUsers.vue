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
	<div class="multiselect-area">
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
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import showNotificationError from './services/Notifications/NotificationError.js'
import NcMultiselect from '@nextcloud/vue/dist/Components/NcMultiselect.js'

export default {
	name: 'MultiSelectUsers',
	components: {
		NcMultiselect,
	},
	props: {
		allSelectedUsers: {
			type: Array,
			default: () => [], // All selected users from all searches
		},
	},
	data() {
		return {
			isLookingUpUsers: false, // True when we are looking up users
			selectableUsers: [], // Users matching a search term
		}
	},
	methods: {
		// Adds user to the batch when user selects user in the MultiSelect
		addUserToBatch(user) {
			this.$emit('change', user)
		},
		displayForSearching({ name, email, uid }) {
			return `${name} - ${email} - ${uid}`
		},
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
		addSubtitleToUsers(users) {
			return users.map(user => {
				return {
					...user,
					subtitle: user.subtitle ?? '',
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
	},
}
</script>

<style>
.select-users-input {
	width: 80%;
}

</style>
