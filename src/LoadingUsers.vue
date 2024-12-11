<template>
	<div>
		<NcEmptyContent>
			<template #icon>
				<NcLoadingIcon :size="64" appearance="dark" name="Loading on light background" />
			</template>
			<template #description v-if="isLoadingUsers" >
				<p>{{ message }}</p>
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

export default {
	name: 'LoadingUsers',
	components: {
		NcEmptyContent,
		NcLoadingIcon,
	},
	props: {
		loadUsers: {
			type: Boolean,
			required: false,
			default: false
		},
		message: {
			type: String,
			required: false,
			default: 'En attente'
		}
	},
	computed: {
		isLoadingUsers() {
			return this.loadUsers
		}
	}
}
</script>

<style>
</style>
