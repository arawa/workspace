<template>
	<NcAppNavigationItem
		:icon="addedGroup ? 'icon-added-group' : 'icon-group'"
		:to="{path: `/group/${spaceId}/${group.slug}`}"
		:name="group.displayName">
		<NcCounterBubble slot="counter" class="user-counter">
			{{ count }}
		</NcCounterBubble>
	</NcAppNavigationItem>
</template>

<script>
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'

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
		spaceId: {
			type: Number,
			required: true,
		},
		addedGroup: {
			type: Boolean,
			required: false,
			default: false,
		},
		count: {
			type: Number,
			required: true,
			default: 0,
		},
	},
}
</script>

<style>
</style>
