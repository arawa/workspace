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
	<NcAppNavigation v-if="$root.$data.canAccessApp === 'true'">
		<NcAppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
			icon="icon-add"
			:title="t('workspace', 'New space')"
			@new-item="createSpace" />
		<NcAppNavigationItem
			:name="t('workspace', 'All spaces')"
			:to="{path: '/'}"
			:class="$route.path === '/' ? 'space-selected' : 'all-spaces'" />
		<template #list>
			<SpaceMenuItem
				v-for="(space, spaceName) in $store.state.spaces"
				:key="space.id"
				:space="space"
				:space-name="spaceName" />
			<!-- <div id="app-settings">
					<div id="app-settings-header">
						<button v-if="$root.$data.isUserGeneralAdmin === 'true'"
							icon="icon-settings-dark"
							class="settings-button"
							data-apps-slide-toggle="#app-settings-content">
							{{ t('workspace', 'Settings') }}
						</button>
					</div>
					<div id="app-settings-content">
						<NcActionButton v-if="$root.$data.isUserGeneralAdmin === 'true'"
							:close-after-click="true"
							:title="t('workspace', 'Convert group folders')"
							@click="toggleShowSelectGroupfoldersModal" />
					</div>
				</div> -->
		</template>
	</NcAppNavigation>
</template>

<script>
import { createSpace } from './services/spaceService.js'
import { PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER } from './constants.js'
import BadCreateError from './Errors/BadCreateError.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import SpaceMenuItem from './SpaceMenuItem.vue'

export default {
	name: 'LeftSidebar',
	components: {
		NcAppNavigation,
		NcAppNavigationNewItem,
		NcAppNavigationItem,
		SpaceMenuItem,
	},
	methods: {
		// Creates a new space and navigates to its details page
		async createSpace(name) {
			if (name === '') {
				showNotificationError('Error', 'Please specify a name.', 3000)
				return
			}

			const REGEX_CHECK_NOTHING_SPECIAL_CHARACTER = new RegExp(PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER)

			if (REGEX_CHECK_NOTHING_SPECIAL_CHARACTER.test(name)) {
				showNotificationError('Error - Creating space', 'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]', 5000)
				throw new BadCreateError(
					'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]',
				)
			}

			const workspace = await createSpace(name, this)

			this.$store.commit('addSpace', {
				color: workspace.color,
				groups: workspace.groups,
				isOpen: false,
				id: workspace.id_space,
				groupfolderId: workspace.folder_id,
				name,
				quota: t('workspace', 'unlimited'),
				users: {},
        userCount: workspace.userCount
			})
			this.$router.push({
				path: `/workspace/${name}`,
			})
		},
	},
}
</script>

<style scoped>
.app-navigation-entry {
	padding-right: 0px;
}
</style>
