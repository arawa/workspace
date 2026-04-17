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
	<div>
		<NcAppNavigation
			aria-label="workspace navigation">
			<ul class="ws-navigation-header">
				<li class="ws-navigation-spacer" />
				<NcButton v-if="$root.$data.isUserGeneralAdmin || $root.$data.isSpaceManager"
					:class="isDarkTheme ? 'btn-dark' : 'btn-light'"
					:aria-label="t('workspace', 'New workspace')"
					:text="t('workspace', 'New workspace')"
					:wide="true"
					@click="openModal">
					<template #icon>
						<Plus />
					</template>
				</NcButton>
				<NcAppNavigationSpacer />
				<NcAppNavigationItem
					:name="t('workspace', 'All workspaces')"
					:to="{ name: 'space.table' }">
					<template #counter>
						<NcCounterBubble :count="$store.state.countTotalWorkspaces" />
					</template>
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
							<button v-if="$root.$data.isUserGeneralAdmin"
								icon="icon-settings-dark"
								class="settings-button"
								data-apps-slide-toggle="#app-settings-content">
								{{ t('workspace', 'Settings') }}
							</button>
						</div>
						<div id="app-settings-content">
							<NcActionButton v-if="$root.$data.isUserGeneralAdmin"
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
		<FormWorkspace v-if="showFormWorkspaceModal"
			:title="t('workspace', 'New workspace')"
			:place-holder-workspace="t('workspace', 'Workspace name')"
			:button-name="t('workspace', 'Create')"
			@click-action="createSpace"
			@close="closeModal" />
	</div>
</template>

<script>
import { createSpace } from './services/spaceService.js'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcAppNavigationNewItem from '@nextcloud/vue/components/NcAppNavigationNewItem'
import NcButton from '@nextcloud/vue/components/NcButton'
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
import Plus from 'vue-material-design-icons/Plus.vue'
import NcAppNavigationSpacer from '@nextcloud/vue/components/NcAppNavigationSpacer'
import FormWorkspace from './components/Modals/FormWorkspace.vue'

export default {
	name: 'LeftSidebar',
	components: {
		NcAppNavigation,
		NcAppNavigationNewItem,
		NcAppNavigationItem,
		NcAppNavigationSpacer,
		NcButton,
		NcCounterBubble,
		SpaceMenuItem,
		PageLoader,
		NcAppNavigationSearch,
		Plus,
		FormWorkspace,
	},
	mixins: [WorkspacesLoader],
	data() {
		return {
			workspacesSearchQuery: this.$store.getters.searchWorkspace || '',
			showFormWorkspaceModal: false,
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
					limit: LIMIT_WORKSPACES_PER_PAGE,
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
		async createSpace(payload) {
			if (payload.name === '') {
				showNotificationError(t('workspace', 'Error'), t('workspace', 'Please specify a name.'), 3000)
				return
			}

			const workspace = await createSpace({
				name: payload.name,
				quota: payload.quota,
				colorCode: payload.colorCode,
			}, this)

			this.$store.commit('addSpace', {
				color: workspace.color,
				groups: workspace.groups,
				added_groups: workspace.added_groups,
				isOpen: false,
				id: workspace.id_space,
				groupfolderId: workspace.folder_id,
				name: payload.name,
				quota: workspace.quota,
				users: {},
				size: 0,
				usersCount: workspace.usersCount,
				managers: null,
			})
			this.$store.dispatch('incrementCountWorkspaces')
			this.$store.dispatch('incrementCountTotalWorkspaces')
			this.$store.dispatch('incrementCountTotalWorkspacesByQuery')
			this.$router.push({
				name: 'space.show',
				params: {
					space: workspace.id_space,
				},
			})
			this.closeModal()
		},
		toggleshowFormWorkspaceModal() {
			this.showFormWorkspaceModal = !this.showFormWorkspaceModal
		},
		openModal() {
			this.showFormWorkspaceModal = true
		},
		closeModal() {
			this.showFormWorkspaceModal = false
		},
	},
}
</script>

<style scoped>
.btn-light {
	background-color: var(--color-main-background);
}

.btn-dark {
	background-color: var(--color-background-dark);
}

.btn-light :hover,
.btn-light :deep(span):hover
.btn-dark :hover,
.btn-dark :deep(span):hover {
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
