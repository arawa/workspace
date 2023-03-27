<template>
	<div class="modal__content">
		<h2>
			{{ t('workspace', 'Are you sure you want to delete the {spaceName} space ?', { spaceName }) }}
		</h2>
		<div class="remove-space-actions">
			<NcButton
				:disabled="disabled"
				:readonly="readonly"
				type="success">
				{{ t('workspace', 'Cancel') }}
			</NcButton>
			<NcButton
				:disabled="disabled"
				:readonly="readonly"
				type="error">
				{{ t('workspace', 'Remove') }}
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
  width: 100%;
  justify-content: space-evenly;
}
</style>
