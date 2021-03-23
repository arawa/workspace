<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div id="content">
		<AppNavigation>
			<AppNavigationNewItem
				title="New space"
				@new-item="onNewSpace" />
			<AppNavigationItem v-for="space in spaces"
				:key="space.name"
				:title="space.name"
				@click="onOpenSpace" />
		</AppNavigation>
	</div>
</template>

<script>
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'

export default {
	name: 'App',
	components: {
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
	},
	data() {
		// TODO: spaces should be retrieved from groupfolders' API
		return {
			spaces: [
				{
					name: 'spaceA',
				},
				{
					name: 'spaceB',
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
</style>
