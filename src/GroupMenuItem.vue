<template>
	<NcAppNavigationItem
		icon="icon-group"
		:to="{path: `/group/${spaceName}/${group.gid}`}"
		:title="group.displayName">
		<NcCounterBubble slot="counter" class="user-counter">
			{{ $store.getters.groupUserCount( spaceName, group.gid) }}
		</NcCounterBubble>
	</NcAppNavigationItem>
</template>

<script>
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'

export default {
	name: 'GroupMenuItem',
	components: {
		NcAppNavigationItem,
		NcCounterBubble,
	},
	props: {
		group: {
			type: Object,
			required: true,
		},
		spaceName: {
			type: String,
			required: true,
		},
		addedGroup: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
}
</script>

<style>
</style>
