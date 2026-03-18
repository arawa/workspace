<template>
	<NcAppContent app-name="hub">
		<div class="hub-home">
			<h1 class="titles-for-space space-title">
				{{ space.name }}
			</h1>
			<div>
				<HubItem
					:path="`/workspace/${spaceId}`"
					:title="t('workspace', 'Users')"
					:path-icon="mdiAccountMultiple" />
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import { mdiAccountMultiple } from '@mdi/js'
import HubItem from '../../components/Hub/HubItem.vue'

export default {
	name: 'HubHome',
	components: {
		NcAppContent,
		HubItem,
	},
	props: {
		spaceId: {
			type: [Number, String],
			required: true,
		},
	},
	setup() {
		return {
			isDarkTheme: useIsDarkTheme(),
		}
	},
	data() {
		return {
			space: null,
			mdiAccountMultiple,
		}
	},
	created() {
		if (this.space === null) {
			this.space = this.$store.getters.getSpaceByNameOrId(this.spaceId)
		}
	},
	updated() {
		this.space = this.$store.getters.getSpaceByNameOrId(this.spaceId)
	},
}
</script>

<style>
.hub-home {
	margin: 12px auto;
	width: 46rem;
	padding: 8px;
}

.space-title {
	margin-bottom: 1.5rem;
}
</style>
