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
			<AppNavigationNew
				text="New space"
				button-id="space_new_space"
				button-class="icon-add"
				@click="onNewSpace" />
			<ul>
				<AppNavigationItem v-for="space in spaces"
					:key="space.name"
					:title="space.name"
					@click="onOpenSpace" />
			</ul>
		</AppNavigation>
	</div>
</template>

<script>
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'

export default {
	name: 'App',
	components: {
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
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
