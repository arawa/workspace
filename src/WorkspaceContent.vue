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
import showNotificationError from './services/Notifications/NotificationError.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAppContentDetails from '@nextcloud/vue/dist/Components/NcAppContentDetails.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { loadState  } from '@nextcloud/initial-state'

export default {
	name: 'WorkspaceContent',
	components: {
		NcAppContent,
		NcAppContentDetails,
		NcLoadingIcon,
	},
	created() {
    this.$store.state.loading = true

    const spaces = loadState('workspace', 'init-workspaces', null)

    if (spaces === null) {
      console.error('Problem to load spaces with initial states')
      const text = t('workspace', 'A network error occured while trying to retrieve workspaces.<br>The error is: {error}', { error: 'Problem to load spaces with initial states' })
      showNotificationError('Network error', text, 5000)
      this.$store.state.loading = false
      return
    }

    spaces.forEach(space => {
      let quota = this.convertQuotaForFrontend(space.quota)
      if (quota === 'unlimited') {
        quota = t('workspace', 'unlimited')
      }

      this.$store.commit('addSpace', {
        color: space.color_code,
        groupfolderId: space.groupfolder_id,
        groups: space.groups,
        added_groups: space.added_groups ?? [],
        id: space.id,
        isOpen: false,
        name: space.name,
        quota,
        users: space.users,
      })

      this.$store.state.loading = false
    })
	},
	methods: {
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
