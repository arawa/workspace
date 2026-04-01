<template>
	<NcAppContent app-name="hub">
		<div class="hub-home">
			<h1 class="titles-for-space space-title">
				{{ space.name }}
			</h1>
			<div v-if="readmeContent"
				class="readme-content">
				<NcRichText class="markdown-content"
					:text="readmeContent"
					:use-markdown="true" />
			</div>
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
import { NcRichText } from '@nextcloud/vue/components/NcRichText'
import { getReadme } from '../../services/DavService.js'

export default {
	name: 'HubHome',
	components: {
		NcAppContent,
		HubItem,
		NcRichText,
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
			readmeContent: null,
			showContent: false,
		}
	},
	created() {
		if (this.space === null) {
			this.space = this.$store.getters.getSpaceByNameOrId(this.spaceId)

			getReadme(this.space.name)
				.then((result) => {
					this.readmeContent = result
				})
				.catch(() => {
					this.readmeContent = null
				})
		}
	},
	updated() {
		this.space = this.$store.getters.getSpaceByNameOrId(this.spaceId)

		getReadme(this.space.name)
			.then((result) => {
				this.readmeContent = result
			})
			.catch(() => {
				this.readmeContent = null
			})
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

.readme-content {
	margin: 1.5rem 0;
}

</style>
