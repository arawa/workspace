<template>
	<div class="modal__content">
		<h2>
			{{ t('workspace', 'Are you sure you want to delete the {spaceName} space ?', { spaceName }) }}
		</h2>
		<div class="remove-space-actions">
			<NcActions style="display: flex; align-items: center;">
				<NcActionButton @click="handleCancel">
					{{ t('workspace', 'Cancel') }}
				</NcActionButton>
				<NcActionButton :close-after-click="true" @click="handleDelete">
					{{ t('workspace', 'Delete') }}
				</NcActionButton>
			</NcActions>
		</div>
	</div>
</template>

<script>
import NcActionButton from '@nextcloud/vue/dist/Components/NcButton.js'
export default {
	name: 'RemoveSpace',
	components: {
		NcActionButton,
	},
	props: {
		spaceName: { type: String, default: '' },
	},
	methods: {
		handleCancel() {
			this.$emit('handle-cancel')
		},
		handleDelete() {
			this.$emit('handle-delete')
		}
	},
}
</script>
<style>
div.modal__content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	margin: 0 auto;
	font-weight: bold;
	font-size: 18px;
}
div.remove-space-actions {
	margin-top: 2rem;
	display: flex;
}
div.remove-space-actions button {
  margin: 0 2rem;
}
</style>
