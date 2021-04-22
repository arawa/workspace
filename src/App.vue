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
				@click="showAllSpaces" />
			<AppNavigationItem v-for="(space, name) in $root.$data.spaces"
				:key="name"
				:class="selectedSpaceName === name ? 'space-selected' : ''"
				:allow-collapse="true"
				:title="name"
				@click="onOpenSpace(name)">
				<div>
					<AppNavigationItem v-for="group in $root.$data.spaces[name].groups"
						:key="group"
						:title="group"
						@click="onOpenGroup(group)" />
				</div>
			</AppNavigationItem>
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<div v-if="selectedSpaceName === 'all'">
					<div class="header" />
					<table>
						<thead>
							<tr>
								<th />
								<th>{{ t('workspace', 'Workspace name') }}</th>
								<th>{{ t('workspace', 'Quota') }}</th>
								<th>{{ t('workspace', 'Space administrators') }}</th>
							</tr>
						</thead>
						<tr v-for="(space,name) in $root.$data.spaces"
							:key="name">
							<td style="width: 50px;">
								<span class="color-dot" :style="{background: space.color}" />
							</td>
							<td> {{ name }} </td>
							<td> {{ space.quota }} </td>
							<td>
								<Avatar v-for="user in adminUsers(space)"
									:key="user"
									:style="{ marginRight: 2 + 'px' }"
									:display-name="user"
									:user="user" />
							</td>
						</tr>
					</table>
				</div>
				<SpaceDetails v-else :space-name="selectedSpaceName" />
			</AppContentDetails>
		</AppContent>
	</Content>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Content from '@nextcloud/vue/dist/Components/Content'
import SpaceDetails from './SpaceDetails'
import Vue from 'vue'

export default {
	name: 'App',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Avatar,
		Content,
		SpaceDetails,
	},
	data() {
		return {
			selectedSpaceName: 'all',
		}
	},
	created() {
		// TODO: spaces should be retrieved from backend
	},
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			return space.users.filter((u) => u.role === 'admin').map((u) => u.name)
		},
		// Creates a new space and directly display its details page
		onNewSpace(spaceName) {
			Vue.set(this.$root.$data.spaces, spaceName, {
				name,
				users: [],
				quota: undefined,
				color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
				groups: [],
			})
			this.selectedSpaceName = spaceName
		},
		onOpenGroup(groupName) {
			// TODO
		},
		// Opens a space's detail page
		onOpenSpace(spaceName) {
			this.selectedSpaceName = spaceName
		},
		// Shows the list of all known spaces
		showAllSpaces() {
			this.selectedSpaceName = 'all'
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

.quota-select {
	max-width: 50px;
}

.color-dot {
	height: 35px;
	width: 35px;
	border-radius: 50%;
	display: block;
}

.space-selected {
	background-color: #f5f5f5;
}

tr:hover {
	background-color: #f5f5f5;
}
</style>
