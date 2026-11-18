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
	<div class="search-users">
		<NcTextField ref="searchInput"
			v-model="term"
			:label="t('workspace', 'Start typing to lookup users')"
			:show-trailing-button="term !== ''"
			@trailing-button-click="term = ''">
			<template #icon>
				<Magnify :size="20" />
			</template>
		</NcTextField>
		<!-- The selected users, between the search and its results as in Talk -->
		<div class="search-users__selection">
			<slot />
		</div>
		<NcLoadingIcon v-if="isLookingUpUsers" class="search-users__loading" />
		<!-- v-show keeps the rows mounted while a lookup runs -->
		<ul v-show="!isLookingUpUsers"
			class="search-users__results"
			role="listbox"
			aria-multiselectable="true"
			:aria-label="t('workspace', 'Search users')">
			<li v-for="user in selectableUsers"
				:key="user.uid"
				class="search-users__option"
				role="option"
				tabindex="0"
				:aria-selected="isSelected(user)"
				@click="$emit('toggle', user)"
				@keydown.enter.space.prevent="$emit('toggle', user)">
				<NcListItemIcon :name="user.name"
					:subname="user.email"
					:user="user.uid"
					:search="term"
					:show-user-status="false">
					<Check v-if="isSelected(user)" :size="20" />
				</NcListItemIcon>
			</li>
		</ul>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import showNotificationError from './services/Notifications/NotificationError.js'
import Check from 'vue-material-design-icons/Check.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import NcListItemIcon from '@nextcloud/vue/components/NcListItemIcon'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import debounce from 'debounce'
import { t } from '@nextcloud/l10n'

export default {
	name: 'MultiSelectUsers',
	components: {
		Check,
		Magnify,
		NcListItemIcon,
		NcLoadingIcon,
		NcTextField,
	},
	props: {
		allSelectedUsers: {
			type: Array,
			default: () => [], // All selected users from all searches
		},
	},
	emits: ['toggle'],
	data() {
		return {
			isLookingUpUsers: false, // True when we are looking up users
			selectableUsers: [], // Users matching a search term
			term: '',
		}
	},
	computed: {
		selectedUids() {
			return new Set(this.allSelectedUsers.map(user => user.uid))
		},
	},
	watch: {
		term(term) {
			if (term.trim() === '') {
				this.debounceLookupUsers.clear()
				this.lookupCount++ // drops the answer of a lookup still running
				this.isLookingUpUsers = false
				this.selectableUsers = []
				return
			}
			this.debounceLookupUsers(term)
		},
	},
	created() {
		// One timer per instance, cleared when the dialog closes
		this.debounceLookupUsers = debounce(this.lookupUsers, 500)
		// Counts the lookups, so that only the answer of the latest one is shown
		this.lookupCount = 0
	},
	beforeUnmount() {
		this.debounceLookupUsers.clear()
		clearTimeout(this.focusTimer)
	},
	mounted() {
		// Waits for the dialog to take the focus first
		this.focusTimer = setTimeout(() => this.$refs.searchInput?.focus(), 100)
	},
	methods: {
		isSelected(user) {
			return this.selectedUids.has(user.uid)
		},
		lookupUsers(term) {
			const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const spaceId = space.id
			// TODO: limit max results?
			const lookup = ++this.lookupCount
			const isLatest = () => lookup === this.lookupCount
			this.isLookingUpUsers = true
			axios.post(generateUrl(`/apps/workspace/api/autoComplete/${encodeURIComponent(term)}/${spaceId}`),
				{
					space,
				})
				.then((resp) => {
					if (!isLatest()) {
						return
					}
					if (resp.status === 200) {
						const users = this.removeDuplicatedUsers(resp.data)
						const usersToDisplay = this.filterAlreadyPresentUsers(users)
						this.selectableUsers = this.addSubtitleToUsers(usersToDisplay)
					} else {
						const text = t('workspace', 'An error occurred while trying to lookup users.<br>Error: {error}', { error: resp.statusText })
						showNotificationError(t('workspace', 'Error'), text, 3000)
					}
				})
				.catch((e) => {
					if (!isLatest()) {
						return
					}
					console.error('Problem to search users', e)
					const text = t('workspace', 'Unable to search for users. Please try again or contact your administrator.')
					showNotificationError(t('workspace', 'Network error'), text, 3000)
				})
				.finally(() => {
					if (isLatest()) {
						this.isLookingUpUsers = false
					}
				})
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
		// When adding users to a space, show only those users who are not already member of the space.
		// Selected users stay listed, checked, so that a click unselects them
		filterAlreadyPresentUsers(recvUsers) {
			const slug = this.$route.params.slug
			if (slug === undefined) {
				const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
				return recvUsers.filter(user => !(user.uid in space.users))
			}
			const gid = decodeURIComponent(decodeURIComponent(slug))
			return recvUsers.filter(user => !user.groups.includes(gid))
		},
	},
}
</script>

<style scoped>
.search-users {
	display: flex;
	flex-direction: column;
	gap: 8px;
	width: 100%;
	height: 100%;
}

/* The selected users and the users found share the height, half each */
.search-users__selection,
.search-users__results,
.search-users__loading {
	flex: 1 1 0;
	min-height: 0;
	overflow-y: auto;
}

.search-users__selection {
	padding-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
}

.search-users__option {
	border-radius: var(--border-radius-element);
	cursor: pointer;
}

.search-users__option:hover,
.search-users__option:focus-visible,
.search-users__option[aria-selected="true"] {
	background-color: var(--color-background-hover);
}
</style>
