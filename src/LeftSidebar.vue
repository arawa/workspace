<template>
  <NcAppNavigation v-if="$root.$data.canAccessApp === 'true'">
    <div>you accessed app</div>
			<NcAppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="createSpace" />
			<NcAppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}"
				:class="$route.path === '/' ? 'space-selected' : 'all-spaces'" />
			<template #list>
				<NcAppNavigationItem
					v-for="(space, spaceName) in $store.state.spaces"
					:key="space.id"
					:class="$route.params.space === spaceName ? 'space-selected' : ''"
					:allow-collapse="true"
					:open="$route.params.space === spaceName"
					:title="spaceName"
					:to="{path: `/workspace/${spaceName}`}">
					<NcAppNavigationIconBullet slot="icon" :color="space.color" />
					<CounterBubble slot="counter" class="user-counter">
						{{ $store.getters.spaceUserCount(spaceName) }}
					</CounterBubble>
					<div>
						<NcAppNavigationItem
							v-for="group in sortedGroups(Object.values(space.groups), spaceName)"
							:key="group.gid"
							icon="icon-group"
							:to="{path: `/group/${spaceName}/${group.gid}`}"
							:title="group.displayName">
							<NcCounterBubble slot="counter" class="user-counter">
								{{ $store.getters.groupUserCount( spaceName, group.gid) }}
							</NcCounterBubble>
						</NcAppNavigationItem>
					</div>
				</NcAppNavigationItem>
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
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble'
export default {
	name: 'LeftSidebar',
  components: {
    NcAppNavigation,
    NcAppNavigationNewItem,
    NcAppNavigationItem,
    NcAppNavigationIconBullet,
    NcCounterBubble,
  }
}
</script>
