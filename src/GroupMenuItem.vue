<template>
	<NcAppNavigationItem
		:to="{path: `/group/${spaceId}/${group.slug}`}"
		:name="group.displayName">
		<template #icon>
			<NcIconSvgWrapper v-if="isAddedGroup && isDarkTheme" :svg="AddedGroupWhite" />
			<NcIconSvgWrapper v-else-if="isAddedGroup && (isDarkTheme === false)" :svg="AddedGroupBlack" />
			<NcIconSvgWrapper v-else :path="mdiAccountMultiple" />
		</template>
		<NcCounterBubble slot="counter" class="user-counter">
			{{ count }}
		</NcCounterBubble>
	</NcAppNavigationItem>
</template>

<script>
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { mdiAccountMultiple } from '@mdi/js'
import AddedGroupBlack from '../img/added_group_black.svg?raw'
import AddedGroupWhite from '../img/added_group_white.svg?raw'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'

export default {
	name: 'GroupMenuItem',
	components: {
		NcAppNavigationItem,
		NcCounterBubble,
		NcIconSvgWrapper,
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
	data() {
		return {
			isDarkTheme: useIsDarkTheme(),
			mdiAccountMultiple,
			AddedGroupBlack,
			AddedGroupWhite,
		}
	},
	computed: {
		isAddedGroup() {
			return this.$store.getters.isSpaceAddedGroup(this.spaceId, this.group.gid)
		},
	},
}
</script>

<style>
</style>
