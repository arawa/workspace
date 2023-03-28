<template>
	<div class="modal__content">
		<h2>
			{{ t('workspace', 'Are you sure you want to delete the {spaceName} space ?', { spaceName }) }}
		</h2>
		<div class="remove-space-actions">
			<NcButton
				type="success"
				@click="handleCancel()">
				{{ t('workspace', 'Cancel') }}
			</NcButton>
			<NcButton
				type="error"
				@click="handleDelete()">
				{{ t('workspace', 'Delete space') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
export default {
	name: 'RemoveSpace',
	components: {
		NcButton,
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
		},
	},
}
</script>
<style>
.modal__content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.modal__content h2 {
  font-weight: bold;
  font-size: 16px;
  width: 80%;
  text-align: center;
}
.remove-space-actions {
	margin-top: 1.5rem;
	display: flex;
}
div.remove-space-actions button {
	margin: 0 1rem;
}
</style>
