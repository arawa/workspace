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
			<div class="container-hub">
				<HubItem
					:path="`/workspace/${spaceId}`"
					:title="t('workspace', 'Users')"
					:path-icon="mdiAccountMultiple" />
				<HubItem
					:path="urlToFolder"
					:title="space.name"
					:svg="isDarkTheme ? App : AppBlack"
					:size="60"
					:external="true"
					:disabled="userNotInSpace" />
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
import { getFolderUrl } from '../../services/spaceService.js'
import App from '../../../img/app.svg?raw'
import AppBlack from '../../../img/app_black.svg?raw'

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
			urlToFolder: null,
			userNotInSpace: false,
			App,
			AppBlack,
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

			getFolderUrl(this.spaceId)
				.then((response) => {
					this.urlToFolder = response.url
					this.userNotInSpace = !response.user_in_group
				})
				.catch((error) => {
					console.error(error.message, error)
					this.urlToFolder = null
					this.userNotInSpace = false
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

		getFolderUrl(this.spaceId)
			.then((response) => {
				this.urlToFolder = response.url
				this.userNotInSpace = !response.user_in_group
			})
			.catch((error) => {
				console.error(error.message, error)
				this.urlToFolder = null
				this.userNotInSpace = false
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

.container-hub {
	display: flex;
	gap: 1.5rem;
	justify-content: space-around;
	flex-wrap: wrap;
	width: 336px;
	margin: 0 auto;
}

</style>
