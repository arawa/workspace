<template>
	<NcAppNavigation v-if="$root.$data.canAccessApp === 'true'">
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
		</template>
	</NcAppNavigation>
</template>

<script>
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import { getLocale } from '@nextcloud/l10n'
export default {
	name: 'LeftSidebar',
	components: {
		NcAppNavigation,
		NcAppNavigationNewItem,
		NcAppNavigationItem,
		NcAppNavigationIconBullet,
		NcCounterBubble,
	},
	methods: {
		// sorts groups alphabetically
		sortedGroups(groups, space) {
			groups.sort((a, b) => {
				// Makes sure the GE- group is first in the list
				// These tests must happen before the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(space)
				if (a === GEGroup) {
					return -1
				}
				if (b === GEGroup) {
					return 1
				}
				// Makes sure the U- group is second in the list
				// These tests must be done after the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(space)
				if (a === UGroup) {
					return -1
				}
				if (b === UGroup) {
					return 1
				}
				// Normal locale based sort
				// Some javascript engines don't support localCompare's locales
				// and options arguments.
				// This is especially the case of the mocha test framework
				try {
					return a.displayName.localeCompare(b.displayName, getLocale(), {
						sensitivity: 'base',
						ignorePunctuation: true,
					})
				} catch (e) {
					return a.displayName.localeCompare(b.displayName)
				}
			})

			return groups
		},
	},
}
</script>
