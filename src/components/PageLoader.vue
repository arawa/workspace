<template>
	<div
		class="loader-next-page-container">
		<div class="loader-next-page-element">
			<NcLoadingIcon :size="24" />
			<span>{{ message }}</span>
		</div>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

export default {
	name: 'PageLoader',
	components: {
		NcLoadingIcon,
	},
	props: {
		message: {
			type: String,
			default: t('workspace', 'Loading...'),
		},
	},
}
</script>

<style scoped>
.loader-next-page-container {
	width: 250px;;
	padding: 16px;
}

.loader-next-page-element {
	display: flex;
	justify-content: left;
	align-items: center;
	gap: 8px;
}
</style>
