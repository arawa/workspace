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
			<AppNavigationItem v-for="space in spaces"
				:key="space.name"
				:title="space.name"
				@click="onOpenSpace(space)" />
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<table v-if="selectedSpace === undefined">
					<thead>
						<tr>
							<th>{{ t('workspace', 'Workspace name') }}</th>
							<th>{{ t('workspace', 'Administrators') }}</th>
							<th>{{ t('workspace', 'Quota') }}</th>
						</tr>
					</thead>
					<tr v-for="space in spaces"
						:key="space.name">
						<td> {{ space.name }} </td>
						<td> {{ adminUsers(space).join(', ') }} </td>
						<td> {{ space.quota }} </td>
					</tr>
				</table>
				<SpaceDetails v-else :space="selectedSpace" />
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
import SpaceDetails from './SpaceDetails'

export default {
	name: 'App',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
		SpaceDetails,
	},
	data() {
		// TODO: spaces should be retrieved from groupfolders' API
		return {
			selectedSpace: undefined,
			spaces: [
				{
					name: 'spaceA',
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
				},
				{
					name: 'spaceB',
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
				},
			],
		}
	},
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			return space.users.filter((u) => u.role === 'admin').map((u) => u.name)
		},
		onNewSpace(name) {
			this.spaces.push(
				{
					name,
					users: [],
					quota: undefined,
				}
			)
		},
		onOpenSpace(space) {
			this.selectedSpace = space
		},
	},
}
</script>

<style scoped>

.app-navigation {
	display: block;
}

</style>
