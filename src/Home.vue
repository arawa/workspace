<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<Content id="content" app-name="workspace">
		<AppNavigation>
			<AppNavigationNewItem
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="onNewSpace" />
			<AppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}" />
			<AppNavigationItem v-for="(space, name) in $root.$data.spaces"
				:key="name"
				:class="$route.params.space === name ? 'space-selected' : ''"
				:allow-collapse="true"
				:open="space.isOpen"
				:title="name"
				:to="{path: `/workspace/${name}`}">
				<div>
					<AppNavigationItem v-for="group in Object.entries($root.$data.spaces[name].groups)"
						:key="group[0]"
						icon="icon-group"
						:to="{path: `/group/${name}/${group[0]}`}"
						:title="group[0]" />
				</div>
			</AppNavigationItem>
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<router-view />
			</AppContentDetails>
		</AppContent>
	</Content>
</template>

<script>
import axios from '@nextcloud/axios'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Content from '@nextcloud/vue/dist/Components/Content'
import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'

export default {
	name: 'Home',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
	},
	created() {
		axios.get(generateUrl('/apps/groupfolders/folders?format=json'))
			.then(resp => {
				const spaces = {}
				const groupfolders = Object.entries(resp.data.ocs.data)
				// TODO: groupfolders should be send to the backend to retrieve the missing info and filter in case user is not GG
				groupfolders.forEach(groupfolder => {
					const folder = groupfolder[1]
					spaces[folder.mount_point] = {
						color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
						groups: folder.groups,
						id: folder.id,
						isOpen: false,
						name: folder.mount_point,
						quota: this.convertQuotaForFrontend(folder.quota),
						users: [],
					}
				})
				this.$root.$data.spaces = spaces
			})
	},
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			return space.users.filter((u) => u.role === 'admin').map((u) => u.name)
		},
		convertQuotaForFrontend(quota) {
			if (quota === '-3') {
				return 'unlimited'
			} else {
				const units = ['', 'KB', 'MB', 'GB', 'TB']
				let i = 0
				while (quota > 1024) {
					quota = quota / 1024
					i++
				}
				return quota + units[i]
			}
		},
		// Creates a new space and navigates to its details page
		onNewSpace(name) {
			if (name === '') {
				// TODO inform user?
				return
			}
			Vue.set(this.$root.$data.spaces, name, {
				color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
				groups: [],
				isOpen: false,
				name,
				quota: undefined,
				users: [],
			})
			this.$router.push({
				path: `/workspace/${name}`,
			})
			// TODO update backend
		},
	},
}
</script>

<style scoped>
.app-content-details {
	display: block;
	margin-left: auto;
	margin-right: auto;
	width: 80%;
}

.app-navigation {
	display: block;
}

.space-selected {
	background-color: #EAF5FC;
}

tr:hover {
	background-color: #f5f5f5;
}
</style>
