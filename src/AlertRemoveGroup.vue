<template>
	<NcModal
		size="normal">
		<div class="container-modal-remove">
			<NcNoteCard type="warning">
				<p v-html="message"></p>
			</NcNoteCard>
			<div class="container-buttons-remove">
				<NcButton
					aria-label="cancel"
					type="secondary"
					@click="cancel()" >
					<template>
						{{ t('workspace', 'Cancel') }}
					</template>
				</NcButton>
				<NcButton
					aria-label="confirm"
					type="primary"
					@click="removeGroup()" >
					<template>
						{{ t('workspace', 'Confirm') }}
					</template>
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

export default {
	name: 'AlertRemoveGroup',
	components: {
		NcButton,
		NcNoteCard,
		NcModal
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
		}
	}
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
