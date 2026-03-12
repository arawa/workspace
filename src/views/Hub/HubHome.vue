<template>
	<NcAppContent app-name="hub">
		<div class="hub-home">
			<h1 class="titles-for-space space-title">
				{{ space.name }}
			</h1>
			<div>
				<NcButton
					:aria-label="t('workspace', 'Go to Workspace')"
					:to="{ path: `/workspace/${spaceId}` }">
					<template #icon>
						<NcIconSvgWrapper v-if="isDarkTheme" name="workspace-icon" :svg="App" />
						<NcIconSvgWrapper v-else name="workspace-icon" :svg="AppBlack" />
					</template>
					<template #default>
						{{ t('workspace', 'Go to Workspace') }}
					</template>
				</NcButton>
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import App from '../../../img/app.svg?raw'
import AppBlack from '../../../img/app_black.svg?raw'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'

export default {
	name: 'HubHome',
	components: {
		NcAppContent,
		NcButton,
		NcIconSvgWrapper,
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
			App,
			AppBlack,
			space: null,
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
