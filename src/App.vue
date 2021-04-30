<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<Content id="content" app-name="workspace">
		<AppNavigation>
			<AppNavigationNewItem
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="onNewSpace" />
			<AppNavigationItem
				icon="icon-home"
				:title="t('workspace', 'All spaces')"
				@click="showAllSpaces" />
			<AppNavigationItem v-for="(space, name) in $root.$data.spaces"
				:key="name"
				:title="name"
				@click="onOpenSpace(name)" />
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<div v-if="selectedSpaceName === 'all'">
					<div class="header" />
					<table>
						<thead>
							<tr>
								<th>{{ t('workspace', 'Workspace name') }}</th>
								<th>{{ t('workspace', 'Administrators') }}</th>
								<th>{{ t('workspace', 'Quota') }}</th>
							</tr>
						</thead>
						<tr v-for="(space,name) in $root.$data.spaces"
							:key="name">
							<td> {{ name }} </td>
							<td> {{ adminUsers(space).join(', ') }} </td>
							<td>
								<Multiselect
									class="quota-select"
									tag-placeholder="t('workspace', 'Add specific quota')"
									:taggable="true"
									:value="space.quota"
									:options="['1GigaByte', '5GigaByte', '10GigaByte', 'unlimited']"
									@change="setSpaceQuota(name, $event)"
									@tag="setSpaceQuota(name, $event)" />
							</td>
						</tr>
						<tr v-for="(workspace, index) in workspaces.result" :key="index">
							<td> {{ workspace.mount_point }} </td>
							<td> {{ adminUsers(workspace).join(', ') }} </td>
							<td>
								<Multiselect
									class="quota-select"
									tag-placeholder="t('workspace', 'Add specific quota')"
									:taggable="true"
									:options="['1GB', '5GB', '10GB', 'unlimited']"
									:value="convertByteToGigaByte(workspace.quota)"
									@change="setSpaceQuota(index, $event)"
									@tag="setSpaceQuota(index, $event)" />
							</td>
						</tr>
					</table>
				</div>
				<SpaceDetails v-else :space-name="selectedSpaceName" />
			</AppContentDetails>
		</AppContent>
	</Content>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Content from '@nextcloud/vue/dist/Components/Content'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import SpaceDetails from './SpaceDetails'
import axios from '@nextcloud/axios'
import Vue from 'vue'

const options = [
	{ id: 1073741824, label: '1GigaByte' },
	{ id: 5368709120, label: '5GigaByte' },
	{ id: 10737418240, label: '10GigaByte' },
	{ id: -3, label: 'unlimited' }
]

export default {
	name: 'App',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
		Multiselect,
		SpaceDetails,
	},
	data() {
		return {
			selectedSpaceName: 'all',
			workspaces: { },
			groupfolders: undefined, // TODO : delete
			options,
			value: options[3],
			selectValue: this.convertByteToGigaByte(options[3].id)
		}
	},
	created() {
		// TODO: spaces should be retrieved from groupfolders' API
		Vue.set(this.$root.$data.spaces, 'spaceA', {
			users: [
				{
					name: 'cyrille',
					role: 'admin',
					email: 'cyrille@bollu.be',
				},
				{
					name: 'dorianne',
					role: 'user',
					email: 'dorianne@arawa.fr',
				},
			],
			quota: undefined,
		})
		Vue.set(this.$root.$data.spaces, 'spaceB', {
			users: [
				{
					name: 'cyrille',
					role: 'admin',
					email: 'cyrille@bollu.be',
				},
				{
					name: 'baptiste',
					role: 'admin',
					email: 'baptiste@arawa.fr',
				},
				{
					name: 'dorianne',
					role: 'user',
					email: 'dorianne@arawa.fr',
				},
			],
			quota: '10GB',
		})
	},
	async beforeMount() {
		const resGroupfodlersApi = await axios.get(OC.generateUrl('apps/groupfolders/folders'))
		const resGroupfoldersApiWithUsers = await this.searchUsersForGroupfolders(resGroupfodlersApi.data.ocs.data)
		Vue.set(this.workspaces, 'result', resGroupfoldersApiWithUsers)
	},
	methods: {
		convertByteToGigaByte(bytes) {
			if (bytes > 0) {
				const gigaByte = (parseInt(bytes) / Math.pow(1024, 3))
				return gigaByte.toString() + 'GB'
			} else {
				return 'unlimited'
			}
		},
		convertGigaByteToByte(gigaByte) {
			if (gigaByte > 0) {
				const byte = (parseInt(gigaByte) * Math.pow(1024, 3))
				return byte
			} else {
				return -3
			}
		},
		filterGigabyteNumber(gigaByte) {
			if (gigaByte === 'unlimited') {
				return -3
			}
			const findNumber = gigaByte.match(/\d+/)
			return parseInt(findNumber[0])
		},
		searchUsersForGroupfolders(groupfolders) {
			for (const i in groupfolders) {
				groupfolders[i].users = []
				const groups = Object.keys(groupfolders[i].groups)
				for (const group of groups) {
					axios.get(OC.generateUrl(`apps/workspace/group/${group}/users`))
						.then(responseUsers => {
							groupfolders[i].users = groupfolders[i].users.concat(responseUsers.data)
						})
				}
			}
			return groupfolders
		},
		// Returns the list of administrators of a space
		adminUsers(space) {
			return space.users.filter((u) => u.role === 'admin').map((u) => u.email)
		},
		// Create a new space
		onNewSpace(spaceName) {
			Vue.set(this.$root.$data.spaces, spaceName, {
				name,
				users: [],
				quota: undefined,
			})
		},
		// Open a space's detail page
		onOpenSpace(spaceName) {
			this.selectedSpaceName = spaceName
		},
		// Set a space's quota
		setSpaceQuota(index, setQuota) {
			// to comment
			const gigaByte = this.filterGigabyteNumber(setQuota)
			const byte = this.convertGigaByteToByte(gigaByte)
		    const myHeaders = new Headers()
			myHeaders.append('OCS-APIRequest', 'true')
			myHeaders.append('Accept', 'application/json')
			// This code doesn't work because I get a 500 error code...
			axios.post(OC.generateUrl(`/apps/groupfolders/folders/${index}/quota`, { quota: byte }, myHeaders))
				.then(response => {
					if (response.status === 200) {
						console.debug('This request work !')
						const workspace = this.workspaces.result[index]
						workspace.quota = byte
						Vue.set(this.workspaces, index, workspace)
					}
				})
				.catch(error => {
					const workspace = this.workspaces.result[index]
					console.error('Error request', error)
					console.error('Error to define the quota for this workspace', workspace)
				})
			// const space = this.$root.$data.spaces[name]
			// space.quota = setQuota
			// Vue.set(this.$root.$data.spaces, name, space)
		},
		// Show the list of all known spaces
		showAllSpaces() {
			this.selectedSpaceName = 'all'
		},
	},
}
</script>

<style scoped>
.app-content-details {
	display: block;
	margin-left: auto;
	margin-right: auto;
	width: 80%;
}

.app-navigation {
	display: block;
}

.quota-select {
	max-width: 50px;
}

tr:hover {
	background-color: #f5f5f5;
}
</style>
