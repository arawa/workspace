<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>

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
	<NcAppNavigationItem
		:key="space.id"
		:class="'workspace-sidebar '+($route.params.space === spaceName ? 'space-selected' : '')"
		:allow-collapse="true"
		:open="$route.params.space === spaceName"
		:name="spaceName"
		@click="loadUsers(spaceName)"
		:to="{path: `/workspace/${spaceName}`}">
		<NcAppNavigationIconBullet slot="icon" :color="space.color" />
		<NcCounterBubble slot="counter" class="user-counter">
			{{ $store.getters.getSpaceUserCount(spaceName) }}
		</NcCounterBubble>
		<MenuItemSelector />
		<NcAppNavigationCaption
			ref="navigationGroup"
			:title="t('workspace', 'Workspace groups')">
			<template #actionsTriggerIcon>
				<Plus :title="t('workspace', 'Create a workspace group')" :size="20" />
			</template>
			<template #actions>
				<NcActionText :class="'space-text'">
					{{ t('workspace', 'Create a workspace group') }}
				</NcActionText>
				<NcActionInput v-show="true"
					ref="createGroupInput"
					:class="'ws-modal-action'"
					icon="icon-group"
					:close-after-click="true"
					:show-trailing-button="true"
					@submit="onNewWorkspaceGroup">
					{{ t('workspace', 'Group name') }}
				</NcActionInput>
			</template>
		</NcAppNavigationCaption>

		<GroupMenuItem
			v-for="group in sortedGroups(Object.values(space.groups ?? []), spaceName)"
			:key="group.gid"
			:group="group"
			:count="group.usersCount"
			:space-name="spaceName" />
		<NcAppNavigationCaption
			:title="t('workspace', 'Added groups')">
			<template #actions>
				<NcActionButton @click="toggleAddGroupModal">
					<template #icon>
						<Plus :size="20" />
					</template>
					{{ t('workspace', 'Add a group')}}
				</NcActionButton>
			</template>
		</NcAppNavigationCaption>
    <SelectConnectedGroups v-if="isAddGroupModalOpen" @close="toggleAddGroupModal" />
		<GroupMenuItem
			v-for="group in sortedGroups(Object.values(space.added_groups ?? []), spaceName)"
			:key="group.gid"
			:group="group"
			:space-name="spaceName"
			:count="group.usersCount"
			:added-group="true" />
	</NcAppNavigationItem>
</template>

<script>
import { getLocale } from '@nextcloud/l10n'
import GroupMenuItem from './GroupMenuItem.vue'
import MenuItemSelector from './MenuItemSelector.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText.js'
import NcAppNavigationCaption from '@nextcloud/vue/dist/Components/NcAppNavigationCaption.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import Plus from 'vue-material-design-icons/Plus.vue'
import SelectConnectedGroups from './SelectConnectedGroups.vue'

export default {
	name: 'SpaceMenuItem',
	components: {
		GroupMenuItem,
		MenuItemSelector,
		NcActionButton,
		NcActionInput,
		NcActionText,
		NcAppNavigationCaption,
		NcAppNavigationIconBullet,
		NcAppNavigationItem,
		NcCounterBubble,
		Plus,
		SelectConnectedGroups,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
		spaceName: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			workspaceGroups: [],
			connectedGroups: [],

			// Added groups
			isAddGroupModalOpen: false,
		}
	},
	methods: {
		// sorts groups alphabetically
		sortedGroups(groups, space) {
			groups.sort((a, b) => {
				// Makes sure the GE- group is first in the list
				// These tests must happen before the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(space)
				if (a.gid === GEGroup) {
					return -1
				}
				if (b.gid === GEGroup) {
					return 1
				}
				// Makes sure the U- group is second in the list
				// These tests must be done after the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(space)
				if (a.gid === UGroup) {
					return -1
				}
				if (b.gid === UGroup) {
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

		toggleAddGroupModal() {
			this.isAddGroupModalOpen = !this.isAddGroupModalOpen
		},

		onNewWorkspaceGroup(e) {
			// Hide and clean popup menu
			this.$refs.navigationGroup.$children.find((child) => child.$options.name === 'NcActions').opened = false
			this.$refs.navigationGroup.$emit('update')

			// Don't accept empty names
			const gid = e.target[0].value
			if (!gid) {
				return
			}
			// Creates group
			this.$store.dispatch('createGroup', { name: this.spaceName, gid })
		},

		loadUsers(spacename) {
			const space = this.$store.state.spaces[spacename]
			this.$store.dispatch('loadUsers', { space })
		},
	}
}
</script>

<style>
.action.space-text {
	padding-left: 44px;
}
.action.ws-modal-action.active {
	background-color: transparent !important;
}
</style>
