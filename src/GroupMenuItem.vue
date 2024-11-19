<template>
	<NcAppNavigationItem
		:icon="addedGroup ? 'icon-added-group' : 'icon-group'"
		:to="{path: `/group/${spaceName}/${group.slug}`}"
		:name="group.displayName">
		<NcCounterBubble slot="counter" class="user-counter">
			{{ count }}
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
	beforeMount() {
		console.debug('Voici le groupe', this.group)
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
		count: {
			type: Number,
			required: true,
			default: 0,
		}
	},
}
</script>

<style>
</style>
