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
		<NcSelect ref="userSelectInput"
			class="select-users-input"
			:filter-by="filterSearched"
			aria-label-combobox="select users"
			label="name"
			:custom-label="displayForSearching"
			:loading="isLookingUpUsers"
			:multiple="false"
			:options="selectableUsers"
			:placeholder="t('workspace', 'Start typing to lookup users')"
			:append-to-body="false"
			:user-select="true"
			@option:selected="addUserToBatch"
			@close="selectableUsers=[]"
			@search="debounceLookupUsers">
			<template #no-options>
				<span />
			</template>
			<template #option="option">
				<div class="option-content">
					<NcAvatar
						class="option-avatar"
						:type="user"
						:display-name="option.name"
						:uid="option.uid"
						:title="option.name"
						:show-user-status="false" />
					<div class="option-text">
						<p>{{ option.name }}</p>
						<small>{{ option.email }}</small>
					</div>
				</div>
			</template>
		</NcSelect>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import showNotificationError from './services/Notifications/NotificationError.js'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import debounce from 'debounce'

export default {
	name: 'MultiSelectUsers',
	components: {
		NcAvatar,
		NcSelect,
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
	mounted() {
		setTimeout(() => {
			const inputElement = this.$refs.userSelectInput.$el.querySelector('input')
			this.$nextTick(() => {
				inputElement.focus()
			})
		}, 100)
	},
	methods: {
		// Adds user to the batch when user selects user in the MultiSelect
		addUserToBatch(user) {
			this.$emit('change', user)
		},
		displayForSearching({ name, email, uid }) {
			return `${name} - ${email} - ${uid}`
		},
		debounceLookupUsers: debounce(function(term) {
			this.lookupUsers(term)
		}, 500),
		lookupUsers(term) {
			// safeguard for initialisation
			if (term === undefined || term === '') {
				return
			}
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const spaceId = space.id
			// TODO: limit max results?
			this.isLookingUpUsers = true
			axios.post(generateUrl(`/apps/workspace/api/autoComplete/${term}/${spaceId}`),
				{
					space,
				})
				.then((resp) => {
					if (resp.status === 200) {
						const users = this.removeDuplicatedUsers(resp.data)
						const usersToDisplay = this.filterAlreadyPresentUsers(users)
						this.selectableUsers = this.addSubtitleToUsers(usersToDisplay)
					} else {
						const text = t('workspace', 'An error occurred while trying to lookup users.<br>The error is: {error}', { error: resp.statusText })
						showNotificationError('Error', text, 3000)
					}
				})
				.catch((e) => {
					const text = t('workspace', 'A network error occurred while trying to lookup users.<br>The error is: {error}', { error: e })
					showNotificationError('Network error', text, 3000)
					console.error('Problem to search users', e)
				})
				.finally(() => {
					this.isLookingUpUsers = false
				})
			this.isLookingUpUsers = false
		},
		removeDuplicatedUsers(users) {
			const usersWithoutDuplicated = Array.from(
				new Set(
					users.map(user => user.uid)))
				.map(uid => users.find(user => user.uid === uid))

			return usersWithoutDuplicated
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
			const group = this.$route.params.slug
			if (group === undefined) {
				const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
				users = recvUsers.filter(user => {
					return (!(user.uid in space.users))
				}, space)
			} else {
				users = recvUsers.filter(user => {
					return (!(user.groups.includes(group)))
				})
			}
			// Filters user that are already selected
			return users.filter(newUser => {
				return this.allSelectedUsers.every(user => {
					return newUser.uid !== user.uid
				})
			})
		},
		filterSearched(option, label, search) {
			const uid = option.uid.toLowerCase()
			const name = option.name.toLowerCase()
			const email = option.email?.toLowerCase()
			const searchTerm = search.toLowerCase()

			return uid.includes(searchTerm)
				|| name.includes(searchTerm)
				|| email?.includes(searchTerm)
		},
	},
}
</script>

<style scoped>
.select-users-input {
	width: 80%;
}

.multiselect-area :deep(.v-select.select.vs--open .vs__dropdown-toggle) {
	border-width: 2px;
	border-color: var(--color-border-dark);
	border-bottom: rgb(0,0,0,0);
}

.multiselect-area:hover :deep(.v-select.select .vs__dropdown-toggle) {
	border-color: var(--color-primary);
}

.multiselect-area:hover :deep(.v-select.select.vs--open) .vs__dropdown-menu {
	border-color: var(--color-primary) !important;
}

.multiselect-area :deep(.v-select.select.vs--open .vs__dropdown-menu) {
	border-width: 2px !important;
	border-color: var(--color-border-dark) !important;
}

.multiselect-area :deep(.v-select.select .vs__dropdown-toggle) {
	border-width: 2px;
	border-color: var(--color-border-dark);
}

.option-content {
	display: flex;
	align-items: center;
	justify-content: start;
	gap: 12px;
}

.option-content, .option-text, .option-avatar {
	cursor: pointer;
}

</style>
