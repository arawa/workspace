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
	<Content id="content" app-name="workspace">
		<notifications
			position="top center"
			width="50%"
			class="notifications"
			:close-on-click="true" />
		<AppNavigation v-if="$root.$data.canAccessApp === 'true'">
			<AppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="createSpace" />
			<AppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}"
				:class="$route.path === '/' ? 'space-selected' : 'all-spaces'" />
			<template #list>
				<AppNavigationItem
					v-for="(space, spaceName) in $store.state.spaces"
					:key="space.id"
					:class="$route.params.space === spaceName ? 'space-selected' : ''"
					:allow-collapse="true"
					:open="$route.params.space === spaceName"
					:title="spaceName"
					:to="{path: `/workspace/${spaceName}`}">
					<AppNavigationIconBullet slot="icon" :color="space.color" />
					<CounterBubble slot="counter" class="user-counter">
						{{ $store.getters.spaceUserCount(spaceName) }}
					</CounterBubble>
					<div>
						<AppNavigationItem
							v-for="group in sortedGroups(Object.values(space.groups), spaceName)"
							:key="group.gid"
							icon="icon-group"
							:to="{path: `/group/${spaceName}/${group.gid}`}"
							:title="group.displayName">
							<CounterBubble slot="counter" class="user-counter">
								{{ $store.getters.groupUserCount( spaceName, group.gid) }}
							</CounterBubble>
						</AppNavigationItem>
					</div>
				</AppNavigationItem>
				<div id="app-settings">
					<div id="app-settings-header">
						<button v-if="$root.$data.isUserGeneralAdmin === 'true'"
							icon="icon-settings-dark"
							class="settings-button"
							data-apps-slide-toggle="#app-settings-content">
							{{ t('workspace', 'Settings') }}
						</button>
					</div>
					<div id="app-settings-content">
						<ActionButton v-if="$root.$data.isUserGeneralAdmin === 'true'"
							:close-after-click="true"
							:title="t('workspace', 'Convert group folders')"
							@click="toggleShowSelectGroupfoldersModal" />
					</div>
				</div>
			</template>
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<div
					v-if="$store.state.loading"
					class="lds-ring">
					<div /><div /><div /><div />
				</div>
				<div v-else class="workspace-content">
					<router-view />
				</div>
			</AppContentDetails>
		</AppContent>
		<Modal
			v-if="showSelectGroupfoldersModal"
			@close="toggleShowSelectGroupfoldersModal">
			<SelectGroupfolders @close="toggleShowSelectGroupfoldersModal" />
		</Modal>
	</Content>
</template>

<script>
import { createSpace, deleteBlankSpacename, isSpaceManagers, isSpaceUsers } from './services/spaceService.js'
import { generateUrl } from '@nextcloud/router'
import { get, formatGroups, createGroupfolder, formatUsers, checkGroupfolderNameExist, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder } from './services/groupfoldersService.js'
import { getLocale } from '@nextcloud/l10n'
import { PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER } from './constants.js'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import axios from '@nextcloud/axios'
import BadCreateError from './Errors/BadCreateError.js'
import Content from '@nextcloud/vue/dist/Components/Content'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import NotificationError from './services/Notifications/NotificationError.js'
import SelectGroupfolders from './SelectGroupfolders.vue'

export default {
	name: 'Home',
	components: {
		ActionButton,
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationIconBullet,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
		Modal,
		SelectGroupfolders,
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
	created() {
		if (Object.entries(this.$store.state.spaces).length === 0) {
			this.$store.state.loading = true
			axios.get(generateUrl('/apps/workspace/spaces'))
				.then(resp => {
					// Checks for application errors
					if (resp.status !== 200) {
						this.$notify({
							title: t('workspace', 'Error'),
							text: t('workspace', 'An error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + resp.statusText,
							type: 'error',
						})
						this.$store.state.loading = false
						return
					}
					this.generateDataCreated(resp.data)
						// resp return [ undefined, undefined, undefined, undefined]
						// it is serious ?
						.then(resp => {
							// Finished loading
							// When all promises is finished
							this.$store.state.loading = false
						})
						.catch(error => {
							console.error('The generateDataCreated method has a problem in promises', error)
							this.$store.state.loading = false
						})
				})
				.catch((e) => {
					console.error('Problem to load spaces only', e)
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + e,
						type: 'error',
					})
					this.$store.state.loading = false
				})
		}
	},
	methods: {
		// Method to generate the data when this component is created.
		// It is necessary to await promises and catch the response to
		// stop the loading.
		// data object/json from space
		generateDataCreated(data) {
			// It possible which the data is not an array but an object.
			// Because, the `/apps/workspace/spaces` route return an object if there is one element.
			if (!Array.isArray(data)) {
				data = [data]
			}
			// loop to build the json final
			const result = Promise.all(data.map(async space => {
				await get(space.groupfolder_id)
					.then((resp) => {
						space.acl = resp.acl
						space.groups = resp.groups
						space.quota = resp.quota
						space.size = resp.size
						return space
					})
					.catch((e) => {
						console.error('Impossible to format the spaces', e)
					})
				const spaceWithUsers = await formatUsers(space)
					.then((resp) => {
						return resp.data
					})
					.catch((error) => {
						console.error('Impossible to generate a space with users format', error)
					})
				const spaceWithUsersAndGroups = await formatGroups(spaceWithUsers)
					.then((resp) => {
						return resp.data
					})
					.catch((error) => {
						console.error('Impossible to generate a space with groups format', error)
					})
				// Initialises the store
				let codeColor = spaceWithUsersAndGroups.color_code
				if (spaceWithUsersAndGroups.color_code === null) {
					codeColor = '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6)
				}
				let quota = this.convertQuotaForFrontend(spaceWithUsersAndGroups.quota)
				if (quota === 'unlimited') {
					quota = t('workspace', 'unlimited')
				}
				// Convert an array empty to object
				if (Array.isArray(spaceWithUsersAndGroups.users)
				&& spaceWithUsersAndGroups.users.length === 0) {
					spaceWithUsersAndGroups.users = { }
				}
				this.$store.commit('addSpace', {
					color: codeColor,
					groupfolderId: spaceWithUsersAndGroups.groupfolder_id,
					groups: spaceWithUsersAndGroups.groups,
					id: spaceWithUsersAndGroups.id,
					isOpen: false,
					name: spaceWithUsersAndGroups.space_name,
					quota,
					users: spaceWithUsersAndGroups.users,
				})
			}))
			return result
		},
		// Shows a space quota in a user-friendly way
		convertQuotaForFrontend(quota) {
			if (quota === -3 || quota === '-3') {
				return 'unlimited'
			} else {
				const units = ['', 'KB', 'MB', 'GB', 'TB']
				let i = 0
				while (quota >= 1024) {
					quota = quota / 1024
					i++
				}
				if (Number.isInteger(quota) === false) {
					quota = quota * 1.024
				}
				return quota + units[i]
			}
		},
		// Creates a new space and navigates to its details page
		async createSpace(name) {
			if (name === '') {
				const toastSpacenameEmpty = new NotificationError(this)
				toastSpacenameEmpty.push({
					title: t('workspace', 'Error'),
					text: t('workspace', 'Please specify a name.'),
					type: 'error',
				})
				return
			}
			name = deleteBlankSpacename(name)

			const REGEX_CHECK_NOTHING_SPECIAL_CHARACTER = new RegExp(PATTERN_CHECK_NOTHING_SPECIAL_CHARACTER)

			if (REGEX_CHECK_NOTHING_SPECIAL_CHARACTER.test(name)) {
				const toastCharacterNotAuthoized = new NotificationError(this)
				toastCharacterNotAuthoized.push({
					title: t('workspace', 'Error - Creating space'),
					text: t(
						'workspace',
						'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]',
					),
					duration: 6000,
				})
				throw new BadCreateError(
					'Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) % \\\\ ^ = / & * ]',
				)
			}

			await checkGroupfolderNameExist(name, this)

			const groupfolderId = await createGroupfolder(name, this)

			await enableAcl(groupfolderId.data.id)

			const workspace = await createSpace(name, groupfolderId.data.id, this)

			const GROUPS_WORKSPACE = Object.keys(workspace.groups)
			const workspaceManagerGid = GROUPS_WORKSPACE.find(isSpaceManagers)
			const workspaceUserGid = GROUPS_WORKSPACE.find(isSpaceUsers)

			await addGroupToGroupfolder(workspace.folder_id, workspaceManagerGid, this)
			await addGroupToGroupfolder(workspace.folder_id, workspaceUserGid, this)

			await addGroupToManageACLForGroupfolder(workspace.folder_id, workspaceManagerGid, this)

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
		// Sorts groups alphabeticaly
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
		toggleShowSelectGroupfoldersModal() {
			this.$store.dispatch('emptyGroupfolders')
			this.showSelectGroupfoldersModal = !this.showSelectGroupfoldersModal
		},
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

.space-selected {
	background-color: #EAF5FC;
}

.all-spaces {
	background-color: inherit !important;
}

.user-counter {
	margin-right: 5px;
}

.notifications {
	margin-top: 70px;
}

.workspace-content {
	height: 100%;
	width: 100%;
}

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
