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
	<NcContent id="content" app-name="workspace">
		<LeftSidebar />
		<WorkspaceContent />
	</NcContent>
</template>

<script>
import { createSpace, deleteBlankSpacename, isSpaceManagers, isSpaceUsers } from './services/spaceService.js'
import { get, formatGroups, createGroupfolder, formatUsers, checkGroupfolderNameExist, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder } from './services/groupfoldersService.js'
import { PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER } from './constants.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationNewItem from '@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js'
import BadCreateError from './Errors/BadCreateError.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import showNotificationError from './services/Notifications/NotificationError.js'
import LeftSidebar from './LeftSidebar.vue'
import WorkspaceContent from './WorkspaceContent.vue'

export default {
	name: 'Home',
	components: {
		NcAppNavigation,
		NcAppNavigationIconBullet,
		NcAppNavigationItem,
		NcAppNavigationNewItem,
		NcContent,
		LeftSidebar,
		WorkspaceContent,
	},
	data() {
		return {
			showSelectGroupfoldersModal: false,
			// notificationError: NotificationError,
		}
	},
	beforeCreate() {
		if (this.$root.$data.canAccessApp === 'false') {
			this.$router.push({
				path: '/unauthorized',
			})
		}
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
		// toggleShowSelectGroupfoldersModal() {
		//  this.$store.dispatch('emptyGroupfolders')
		//  this.showSelectGroupfoldersModal = !this.showSelectGroupfoldersModal
		// },
	},
}
</script>

<style scoped>

.app-content-details {
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100%;
}

.app-navigation-entry {
	padding-right: 0px;
}

.user-counter {
	margin-right: 5px;
}

/* .notifications {
	margin-top: 70px;
} */

/* .workspace-content {
	height: 100%;
	width: 100%;
} */

/*
	Code for the loading.
	Source code: https://loading.io/css/
*/
.lds-ring {
	display: inline-block;
	position: relative;
	width: 80px;
	height: 80px;
}

.lds-ring div {
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 64px;
	height: 64px;
	margin: 8px;
	border: 8px solid var(--color-primary-element);
	border-radius: 50%;
	animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
	border-color: var(--color-primary-element) transparent transparent transparent;
}

.lds-ring div:nth-child(1) {
	animation-delay: -0.45s;
}

.lds-ring div:nth-child(2) {
	animation-delay: -0.3s;
}

.lds-ring div:nth-child(3) {
	animation-delay: -0.15s;
}

@keyframes lds-ring {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}

</style>
