<template>
	<div class="modal__content">
		<h2>
			{{ t('workspace', 'Are you sure you want to delete the {spaceName} space ?', { spaceName }) }}
		</h2>
	</div>
</template>

<script>
export default {
	name: 'RemoveSpace',
	props: {
		spaceName: { type: String, default: '' },
	},
}
</script>
<style>
div.modal__content {
	display: flex;
	align-items: center;
	margin: 0 auto;
	font-weight: bold;
	font-size: 18px;
}
</style>
