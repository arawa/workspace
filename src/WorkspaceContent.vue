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
	<NcAppContent>
		<NcAppContentDetails>
			<div
				v-if="$store.state.loading">
				<NcLoadingIcon :size="64" appearance="dark" name="Loading on light background" />
			</div>
			<div v-else class="workspace-content">
				<router-view />
			</div>
		</NcAppContentDetails>
	</NcAppContent>
	<!-- <NcModal
			v-if="showSelectGroupfoldersModal"
			@close="toggleShowSelectGroupfoldersModal">
			<SelectGroupfolders @close="toggleShowSelectGroupfoldersModal" />
	</NcModal> -->
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import showNotificationError from './services/Notifications/NotificationError.js'
import { get, formatGroups, formatUsers } from './services/groupfoldersService.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAppContentDetails from '@nextcloud/vue/dist/Components/NcAppContentDetails.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
export default {
	name: 'WorkspaceContent',
	components: {
		NcAppContent,
		NcAppContentDetails,
		NcLoadingIcon,
	},
	created() {
		if (Object.entries(this.$store.state.spaces).length === 0) {
			this.$store.state.loading = true
			axios.get(generateUrl('/apps/workspace/spaces'))
				.then(resp => {
					// Checks for application errors
					if (resp.status !== 200) {
						const text = t('workspace', 'An error occured while trying to retrieve workspaces.<br>The error is: {error}', { error: resp.statusText })
						showNotificationError('Error', text, 4000)
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
					const text = t('workspace', 'A network error occured while trying to retrieve workspaces.<br>The error is: {error}', { error: e })
					showNotificationError('Network error', text, 5000)
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
					addedGroups: spaceWithUsersAndGroups.addedGroups,
					id: spaceWithUsersAndGroups.id,
					isOpen: false,
					name: spaceWithUsersAndGroups.name,
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
	},
}
</script>

<style scoped>
/*
	Code for the loading.
	Source code: https://loading.io/css/
*/
.workspace-content {
	height: 100%;
	width: 100%;
}
.app-content-details {
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100%;
}
</style>
