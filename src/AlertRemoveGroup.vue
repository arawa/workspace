<template>
	<NcModal
		name="alert remove group"
		label-id="alert remove group"
		size="normal">
		<div class="container-modal-remove">
			<NcNoteCard type="warning">
				<p v-html="message" />
			</NcNoteCard>
			<div class="container-buttons-remove">
				<NcButton
					aria-label="cancel"
					type="secondary"
					@click="cancel()">
					<template>
						{{ t('workspace', 'Cancel') }}
					</template>
				</NcButton>
				<NcButton
					aria-label="confirm"
					type="primary"
					@click="removeGroup()">
					<template>
						{{ t('workspace', 'Confirm') }}
					</template>
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
