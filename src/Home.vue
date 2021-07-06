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
			<AppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="createSpace" />
			<AppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}" />
			<AppNavigationItem v-for="(space, name) in $store.state.spaces"
				:key="name"
				:class="$route.params.space === name ? 'space-selected' : ''"
				:allow-collapse="true"
				:open="space.isOpen"
				:title="name"
				:to="{path: `/workspace/${name}`}">
				<AppNavigationIconBullet slot="icon" :color="space.color" />
				<CounterBubble slot="counter" class="user-counter">
					{{ $store.getters.spaceUserCount(name) }}
				</CounterBubble>
				<div>
					<AppNavigationItem v-for="group in Object.entries($store.state.spaces[name].groups)"
						:key="group[0]"
						icon="icon-group"
						:to="{path: `/group/${name}/${group[0]}`}"
						:title="group[0]">
						<CounterBubble slot="counter" class="user-counter">
							{{ $store.getters.groupUserCount( name, group[0]) }}
						</CounterBubble>
					</AppNavigationItem>
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
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Content from '@nextcloud/vue/dist/Components/Content'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Home',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationIconBullet,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
	},
	created() {
		axios.get(generateUrl('/apps/workspace/spaces'))
			.then(resp => {
				Object.values(resp.data).forEach(space => {
					this.$store.commit('addSpace', {
						// TODO color should be returned by backend
						color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
						groups: space.groups,
						id: space.id,
						groupfolderId: space.groupfolder_id,
						isOpen: false,
						name: space.space_name,
						quota: this.convertQuotaForFrontend(space.quota),
						users: space.users,
					})
				})
			})
	},
	methods: {
		// Shows a space quota in a user-friendly way
		convertQuotaForFrontend(quota) {
			if (quota === '-3') {
				return 'unlimited'
			} else {
				const units = ['', 'KB', 'MB', 'GB', 'TB']
				let i = 0
				while (quota >= 1024) {
					quota = quota / 1024
					i++
				}
				return quota + units[i]
			}
		},
		// Creates a new space and navigates to its details page
		createSpace(name) {
			if (name === '') {
				// TODO inform user?
				return
			}
			axios.post(generateUrl('/apps/workspace/spaces'),
				{
					spaceName: name,
				}
			)
				.then(resp => {
					this.$store.commit('addSpace', {
						color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
						groups: resp.data.groups,
						isOpen: false,
						id: resp.data.id_space,
						name,
						quota: undefined,
						users: [],
					})
					this.$router.push({
						path: `/workspace/${name}`,
					})
				})
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

.user-counter {
	margin-right: 5px;
}
</style>
