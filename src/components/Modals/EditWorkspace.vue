<template>
	<NcModal
		:show="show"
		size="large"
		class="modal"
		@close="close">
		<div class="modal__container">
			<h1>{{ t('workspace', 'Edit the Workspace') }}</h1>
			<h2>{{ t('workspace', 'Appearance') }}</h2>
			<div class="content-appearance">
				<NcColorPicker ref="colorPicker"
					:value="color"
					class="space-color-picker"
					@update:value="updateColor">
					<button class="color-dot color-picker"
						:style="{backgroundColor: color}" />
				</NcColorPicker>
				<NcInputField class="input-spacename"
					:value.sync="spacename"
					:placeholder="t('workspace', 'Rename your Workspace')"
					type="text" />
			</div>
			<h2>{{ t('workspace', 'Quota') }}</h2>
			<div class="content-quota">
				<p>{{ t('workspace', 'Set maximum Workspace storage space') }}</p>
				<NcSelect :value="getQuota"
					class="quota-input"
					:clear-search-on-select="false"
					:taggable="true"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:multiple="false"
					:clearable="false"
					:options="['1GB', '5GB', '10GB', t('workspace','unlimited')]"
					@option:selected="updateQuota" />
				<p>Vous utilisez <b>{{ getSize }}</b> sur {{ getQuota }}</p>
				<NcProgressBar class="progress-bar"
					size="medium"
					:value="calculPercentSize"
					:error="isError" />
				<NcNoteCard v-if="isError"
					type="warning">
					<p>{{ t('workspace', 'Please note that the quota you have selected is less than the space currently used by your Workspace. You will no longer be able to add or modify files.') }}</p>
				</NcNoteCard>
			</div>
			<NcButton aria-label="Save"
				class="btn-save"
				@click="save">
				{{ t('workspace', 'Save') }}
				<template #icon>
					<Check />
				</template>
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Check from 'vue-material-design-icons/Check.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import { renameSpace } from '../../services/spaceService.js'
import showNotificationError from '../../services/Notifications/NotificationError.js'

export default {
	name: 'EditWorkspace',
	components: {
		Check,
		NcButton,
		NcModal,
		NcNoteCard,
		NcColorPicker,
		NcInputField,
		NcSelect,
		NcProgressBar,
	},
	props: {
		show: {
			type: Boolean,
			required: true,
			default: false,
		},
	},
	data() {
		return {
			spacename: '',
			color: '',
			quota: '',
			size: '',
		}
	},
	computed: {
		calculPercentSize() {
			const res = (this.size * 100) / this.quota
			if (res < 100 && this.quota !== -3) {
				return res
			}
			return 100
		},
		isError() {
			if (this.size >= this.quota && this.quota !== -3) {
				return true
			}
			return false
		},
		getQuota() {
			return this.$store.getters.convertQuotaForFrontend(this.quota)
		},
		getSize() {
			return this.$store.getters.convertQuotaForFrontend(this.size)
		},
		getQuotaMessage() {
			return t('workspace', 'You use {size} on {quota}', { size: this.size, quota: this.quota })
		},
	},
	beforeMount() {
		this.color = this.$store.state.spaces[this.$route.params.space].color
		this.quota = this.$store.state.spaces[this.$route.params.space].quota
		this.size = this.$store.state.spaces[this.$route.params.space].size
	},
	methods: {
		close() {
			this.$emit('close')
		},
		async save() {
			const oldSpaceName = this.$store.state.spaces[this.$route.params.space].name
			const space = { ...this.$store.state.spaces[oldSpaceName] }

			if (this.color !== space.color) {
				axios.post(generateUrl(`/apps/workspace/workspaces/${this.$store.state.spaces[oldSpaceName].id}/color`),
					{
						colorCode: this.color,
					})
					.then(resp => {
						this.$store.dispatch('updateColor', {
							name: oldSpaceName,
							colorCode: this.color,
						})
					})
					.catch(err => {
						const text = t('workspace', 'A network error occured when trying to change the workspace\'s color.<br>The error is: {error}', { error: err })
						showNotificationError('Network error', text, 3000)
					})
			}

			if ((oldSpaceName !== this.spacename) && (this.spacename !== '')) {
				let responseRename = await renameSpace(this.$store.state.spaces[oldSpaceName].id, this.spacename)
				responseRename = responseRename.data

				if (responseRename.statuscode === 204) {
					const spaceBeforeRenamed = { ...this.$store.state.spaces[oldSpaceName] }
					spaceBeforeRenamed.name = responseRename.space
					space.name = responseRename.space

					this.$store.dispatch('updateSpace', {
						space: spaceBeforeRenamed,
					})
					this.$store.dispatch('removeSpace', {
						space: this.$store.state.spaces[oldSpaceName],
					})

					const groupKeys = Object.keys(spaceBeforeRenamed.groups)
					groupKeys.forEach(key => {
						const group = spaceBeforeRenamed.groups[key]
						if (!this.checkSpaceNameIsEqual(group.displayName, oldSpaceName)) {
							group.displayName = this.replaceSpaceName(group.displayName, oldSpaceName)
						}
						const newDisplayName = group.displayName.replace(oldSpaceName, this.spacename)
						// Renames group
						this.$store.dispatch('renameGroup', {
							name: this.spacename,
							gid: group.gid,
							newGroupName: newDisplayName,
						})
					})
				}
			}

			if (space.quota !== this.quota) {
				this.$store.dispatch('setSpaceQuota', {
					name: space.name,
					quota: this.quota,
				})
			}

			if ((oldSpaceName !== this.spacename) && (this.spacename !== '')) {
				this.$router.push({
					path: `/workspace/${space.name}`,
				})
			}

			this.spacename = ''

			this.$emit('close')

		},
		/**
		 * @param {string} groupname the displayname from a group
		 * @param {string} oldSpaceName the currently space name
		 * To fix a bug from release 3.0.2
		 */
		checkSpaceNameIsEqual(groupname, oldSpaceName) {
			let spaceNameFiltered = ''

			if (groupname.startsWith('U-')) {
				spaceNameFiltered = groupname.replace('U-', '')
			}

			if (groupname.startsWith('WM-')) {
				spaceNameFiltered = groupname.replace('WM-', '')
			} else if (groupname.startsWith('GE-')) {
				spaceNameFiltered = groupname.replace('GE-', '')
			}

			if (groupname.startsWith('G-')) {
				spaceNameFiltered = groupname.replace('G-', '')
			}

			if (spaceNameFiltered === oldSpaceName) {
				return true
			}

			return false
		},
		/**
		 * @param {string} groupname the displayname from a group
		 * @param {string} oldSpaceName the currently space name
		 * To fix a bug from release 3.0.2
		 */
		replaceSpaceName(groupname, oldSpaceName) {
			const spaceNameSplitted = groupname
				.split('-')
				.filter(element => element)

			if (spaceNameSplitted[0] === 'WM'
					|| spaceNameSplitted[0] === 'U') {
				spaceNameSplitted[1] = oldSpaceName
			}

			if (spaceNameSplitted[0] === 'G') {
				const lengthMax = spaceNameSplitted.length - 1
				spaceNameSplitted[lengthMax] = oldSpaceName
			}

			return spaceNameSplitted.join('-')
		},
		updateColor(e) {
			this.color = e
		},
		updateQuota(quota) {
			if (quota === null) {
				return
			}
			const control = new RegExp(`^(${t('workspace', 'unlimited')}|\\d+(tb|gb|mb|kb)?)$`, 'i')
			if (!control.test(quota)) {
				const text = t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota')
				showNotificationError('Error', text, 3000)
				return
			}

			quota = this.$store.getters.convertQuotaToByte(quota)

			this.quota = quota
		},
	},
}
</script>

<style scoped>
.modal :deep(.modal-wrapper .modal-container) {
	min-height: auto;
	padding: 16px;
	width: 700px;
}

.modal__container {
	display: flex;
	flex-direction: column;
	margin: 10px;
}

.btn-save {
	align-self: end;
	margin: 8px;
}

h1 {
	font-size: 20px;
	font-weight: bold;
}

h2 {
	font-size: 16px;
}

.input-spacename :deep(div input) {
	width: 70%;
}

.content-appearance {
	display: flex;
}

.content-quota {
	display: flex;
	flex-direction: column;
}

.quota-input {
	width: 200px;
	margin: 16px 0 16px 0 !important;
}

.progress-bar {
	width: 300px !important;
	margin: 8px 0 8px 0 !important;
}
</style>
