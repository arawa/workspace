<template>
	<NcRichText
		:text="readmeContent"
		:use-markdown="true" />
</template>

<script>
import { getReadme } from '../../services/spaceService.js'
import NcRichText from '@nextcloud/vue/components/NcRichText'

export default {
	name: 'ContentReadme',
	components: {
		NcRichText,
	},
	props: {
		spaceId: {
			type: [String, Number],
			required: true,
		},
	},
	data() {
		return {
			readmeContent: null,
			showContent: false,
		}
	},
	created() {
		getReadme(this.spaceId)
			.then((response) => {
				console.debug('README.md content fetched successfully:', response)
				if (response.success) {
					this.readmeContent = response.content
				}
			})
			.catch((error) => {
				console.error(error.message, error)
				this.readmeContent = null
			})
	},
	updated() {
		console.debug('ContentReadme component updated, fetching README.md content for spaceId:', this.spaceId)
		getReadme(this.spaceId)
			.then((response) => {
				console.debug('README.md content fetched successfully:', response)
				if (response.success) {
					this.readmeContent = response.content
				} else {
					this.readmeContent = null
				}
			})
			.catch((error) => {
				console.error(error.message, error)
				this.readmeContent = null
			})
	},
}
</script>
