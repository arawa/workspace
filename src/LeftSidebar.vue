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
				<NcCounterBubble slot="counter" class="user-counter">
					{{ $store.getters.spaceUserCount(spaceName) }}
				</NcCounterBubble>
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
import { createSpace, deleteBlankSpacename, isSpaceManagers, isSpaceUsers } from './services/spaceService.js'
import { createGroupfolder, checkGroupfolderNameExist, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder } from './services/groupfoldersService.js'
import { PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER } from './constants.js'
import BadCreateError from './Errors/BadCreateError.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import { getLocale } from '@nextcloud/l10n'
import showNotificationError from './services/Notifications/NotificationError.js'
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
		// Creates a new space and navigates to its details page
		async createSpace(name) {
			if (name === '') {
				showNotificationError('Error', 'Please specify a name.', 3000)
				return
			}
			name = deleteBlankSpacename(name)

			const REGEX_CHECK_NOTHING_SPECIAL_CHARACTER = new RegExp(PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER)

			if (REGEX_CHECK_NOTHING_SPECIAL_CHARACTER.test(name)) {
				showNotificationError('Error - Creating space', 'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]', 5000)
				throw new BadCreateError(
					'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]',
				)
			}

			await checkGroupfolderNameExist(name)

			const groupfolderId = await createGroupfolder(name)

			await enableAcl(groupfolderId.data.id)

			const workspace = await createSpace(name, groupfolderId.data.id)

			const GROUPS_WORKSPACE = Object.keys(workspace.groups)
			const workspaceManagerGid = GROUPS_WORKSPACE.find(isSpaceManagers)
			const workspaceUserGid = GROUPS_WORKSPACE.find(isSpaceUsers)

			await addGroupToGroupfolder(workspace.folder_id, workspaceManagerGid)
			await addGroupToGroupfolder(workspace.folder_id, workspaceUserGid)

			await addGroupToManageACLForGroupfolder(workspace.folder_id, workspaceManagerGid)

			this.$store.commit('addSpace', {
				color: workspace.color,
				groups: workspace.groups,
				isOpen: false,
				id: workspace.id_space,
				groupfolderId: groupfolderId.data.id,
				name,
				quota: t('workspace', 'unlimited'),
				users: {},
			})
			this.$router.push({
				path: `/workspace/${name}`,
			})
		},
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
	},
}
</script>

<style scoped>
.app-navigation-entry {
	padding-right: 0px;
}
.user-counter {
	margin-right: 5px;
}
</style>
