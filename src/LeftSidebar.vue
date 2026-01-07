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
		<ul class="ws-navigation-header">
			<NcAppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				class="input-new-item"
				:class="isDarkTheme ? 'btn-dark' : 'btn-light'"
				icon="icon-add"
				:name="t('workspace', 'New workspace')"
				@new-item="createSpace" />
			<li class="ws-navigation-spacer" />
			<NcAppNavigationItem
				:name="t('workspace', 'All workspaces')"
				:to="{path: '/'}">
				<NcCounterBubble slot="counter">
					{{ $store.state.countTotalWorkspaces }}
				</NcCounterBubble>
			</NcAppNavigationItem>
		</ul>
		<NcAppNavigationSearch v-model="workspacesSearchQuery"
			:label="t('workspace', 'Search workspaces...')" />
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
							:title="t('workspace', 'Convert Team folders')"
							@click="toggleShowSelectGroupfoldersModal" />
					</div>
				</div> -->
			<div v-if="Object.keys($store.state.spaces).length">
				<PageLoader v-if="nextPage"
					v-element-visibility="next"
					:message="messageLoader" />
			</div>
		</template>
	</NcAppNavigation>
</template>

<script>
import { createSpace } from './services/spaceService.js'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcAppNavigationNewItem from '@nextcloud/vue/components/NcAppNavigationNewItem'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import showNotificationError from './services/Notifications/NotificationError.js'
import SpaceMenuItem from './SpaceMenuItem.vue'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import PageLoader from './components/PageLoader.vue'
import { WorkspacesLoader } from './mixins/WorkspacesLoader.mixin.js'
import debounce from 'debounce'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { LIMIT_WORKSPACES_PER_PAGE } from './constants.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'LeftSidebar',
	components: {
		NcAppNavigation,
		NcAppNavigationNewItem,
		NcAppNavigationItem,
		NcCounterBubble,
		SpaceMenuItem,
		PageLoader,
		NcAppNavigationSearch,
	},
	mixins: [WorkspacesLoader],
	data() {
		return {
			workspacesSearchQuery: this.$store.getters.searchWorkspace,
		}
	},
	computed: {
		isDarkTheme() {
			return useIsDarkTheme().value
		},
	},
	watch: {
		workspacesSearchQuery(query) {
			this.$store.dispatch('updateSearchWorkspace', { search: query })
			this.debounceSearch()
		},
	},
	methods: {
		debounceSearch: debounce(function() {
			this.search()
		}, 500),
		search() {
			axios.get(generateUrl('apps/workspace/workspaces/count'), {
				params: {
					search: this.$store.getters.searchWorkspace,
				},
			})
				.then(resp => {
					const count = resp.data.count
					this.$store.dispatch('setCountTotalWorkspacesByQuery', { count })
				})

			axios.get(generateUrl('/apps/workspace/spaces'), {
				params: {
					search: this.$store.getters.searchWorkspace,
					limit: LIMIT_WORKSPACES_PER_PAGE
				},
			})
				.then(resp => {
					const spaces = resp.data

					this.$store.commit('setSpaces', { spaces })
					this.$store.dispatch('setCountWorkspaces', { count: Object.values(spaces).length })
					this.$store.dispatch('initWorkspacePage')
					this.$store.dispatch('resetNextPage')

					this.showNextPage()
				})
				.catch((e) => {
					console.error('Problem to search workspaces', e)
				})
		},
		// Creates a new space and navigates to its details page
		async createSpace(name) {
			if (name === '') {
				showNotificationError(t('workspace', 'Error'), t('workspace', 'Please specify a name.'), 3000)
				return
			}

			const workspace = await createSpace(name, this)

			this.$store.commit('addSpace', {
				color: workspace.color,
				groups: workspace.groups,
				added_groups: workspace.added_groups,
				isOpen: false,
				id: workspace.id_space,
				groupfolderId: workspace.folder_id,
				name,
				quota: workspace.quota,
				users: {},
				userCount: workspace.userCount,
				managers: null,
			})
			this.$store.dispatch('incrementCountWorkspaces')
			this.$store.dispatch('incrementCountTotalWorkspaces')
			this.$store.dispatch('incrementCountTotalWorkspacesByQuery')
			this.$router.push({
				path: `/workspace/${workspace.id_space}`,
			})
		},
	},
}
</script>

<style scoped>
.input-new-item :deep(.app-navigation-entry-button) {
	align-items: center;
}

.input-new-item :deep(.button-vue--icon-only) {
	height: 20px;
}

.input-new-item :deep(.app-navigation-input-confirm form) {
	align-items: center;
}

.input-new-item :deep(.app-navigation-entry-button .app-navigation-entry-icon) {
	margin-right: 4px;
}

.btn-light :deep(.app-navigation-entry-button) {
	background-color: var(--color-main-background);
}

.btn-dark :deep(.app-navigation-entry-button) {
	background-color: var(--color-background-dark);
}

.btn-light :deep(.app-navigation-entry-button):hover,
.btn-dark :deep(.app-navigation-entry-button):hover {
	background-color: var(--color-background-hover);
}

.app-navigation-entry {
	padding-right: 0px;
}
.ws-navigation-header {
	padding: var(--app-navigation-padding);
}
.ws-navigation-spacer {
	height: var(--app-navigation-padding);
}
	</style>
