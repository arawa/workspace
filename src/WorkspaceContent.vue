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
			<div class="workspace-content">
				<LoadingUsers v-if="$store.state.loadingUsersWaiting" :load-users="true" />
				<LoadingUsers v-if="$store.state.loading" />
				<router-view v-else />
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
import { getSpace } from './services/spaceService.js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppContentDetails from '@nextcloud/vue/components/NcAppContentDetails'
import LoadingUsers from './LoadingUsers.vue'
import { LIMIT_WORKSPACES_PER_PAGE } from './constants.js'

export default {
	name: 'WorkspaceContent',
	components: {
		NcAppContent,
		NcAppContentDetails,
		LoadingUsers,
	},
	created() {
		if (Object.entries(this.$store.state.spaces).length === 0) {
			this.$store.state.loading = true
			axios.get(generateUrl('/apps/workspace/workspaces'), {
				params: {
					limit: LIMIT_WORKSPACES_PER_PAGE,
				},
			})
				.then(async resp => {
					// Checks for application errors
					if (resp.status !== 200) {
						const text = t('workspace', 'An error occurred while trying to retrieve workspaces.<br>Error: {error}', { error: resp.statusText })
						showNotificationError(t('workspace', 'Error'), text, 4000)
						this.$store.state.loading = false
						return
					}

					const spaces = resp.data
					this.$store.commit('setSpaces', { spaces })
					await this.loadRequestedSpace()
					this.$store.dispatch('setCountWorkspaces', { count: Object.keys(this.$store.state.spaces).length })

					this.$store.state.loading = false

					// A user who sees a single workspace lands on its page, unless the URL asks for one
					if (Object.values(spaces).length === 1 && this.requestedSpaceId() === undefined) {
						this.$router.push({ name: 'space.show', params: { space: Object.values(spaces)[0].id } })
					}
				})
				.catch((e) => {
					console.error('Problem to load spaces only', e)
					const text = t('workspace', 'Unable to load the workspaces. Please try again or contact your administrator.')
					showNotificationError(t('workspace', 'Network error'), text, 5000)
					this.$store.state.loading = false
				})
		}
	},
	methods: {
		// The workspace the URL asks for: that of the page of a workspace, of a group or of a hub
		requestedSpaceId() {
			return this.$route.params.space ?? this.$route.params.spaceId
		},
		// Loads the workspace the page was opened on, from its URL, when it is not on the first
		// page of the list, which the navigation loads alone. Only an id can be looked up
		async loadRequestedSpace() {
			const spaceId = this.requestedSpaceId()
			if (!/^\d+$/.test(spaceId) || this.$store.getters.getSpaceById(Number(spaceId)) !== undefined) {
				return
			}

			try {
				this.$store.commit('addSpaces', { spaces: await getSpace(spaceId) })
			} catch (e) {
				console.error('Problem to load the workspace', spaceId, e)
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
