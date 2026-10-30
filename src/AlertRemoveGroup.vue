<template>
	<NcModal
		label-id="alert-remove-group-message"
		size="normal">
		<div class="container-modal-remove">
			<NcNoteCard type="warning">
				<p id="alert-remove-group-message" v-html="message" />
			</NcNoteCard>
			<div class="container-buttons-remove">
				<NcButton
					variant="secondary"
					@click="cancel()">
					{{ t('workspace', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					@click="removeGroup()">
					{{ t('workspace', 'Confirm') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

export default {
	name: 'AlertRemoveGroup',
	components: {
		NcButton,
		NcNoteCard,
		NcModal,
	},
	props: {
		message: {
			type: String,
			required: true,
		},
	},
	methods: {
		cancel() {
			this.$emit('cancel')
		},
		removeGroup() {
			this.$emit('remove-group')
		},
	},
}
</script>

<style scoped>
.container-buttons-remove {
	display: flex;
	justify-content: space-between;
	margin-top: 24px;
}

.container-modal-remove {
	padding: 24px;
	margin-top: 20px;
}

</style>
