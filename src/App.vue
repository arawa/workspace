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
				title="New space"
				@new-item="onNewSpace" />
			<AppNavigationItem v-for="space in spaces"
				:key="space.name"
				:title="space.name"
				@click="onOpenSpace" />
		</AppNavigation>
		<AppContent>
			<AppContentDetails />
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

export default {
	name: 'App',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
	},
	data() {
		// TODO: spaces should be retrieved from groupfolders' API
		return {
			spaces: [
				{
					name: 'spaceA',
					quota: '',
				},
				{
					name: 'spaceB',
					quota: '10GB',
				},
			],
		}
	},
	methods: {
		onNewSpace() {
			// TODO
		},
		onOpenSpace() {
			// TODO
		},
	},
}
</script>

<style scoped>
#app-navigation-vue {
	display: block;
}
</style>
