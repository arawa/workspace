<template>
	<NcModal
		name="edit workspace"
		label-id="edit workspace"
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
					:value="getSpaceName"
					:placeholder="t('workspace', 'Rename your Workspace')"
					type="text"
					@update:value="updateSpacename" />
			</div>
			<h2>{{ t('workspace', 'Quota') }}</h2>
			<div class="content-quota">
				<p class="max-contrast">
					{{ t('workspace', 'Set maximum Workspace storage space') }}
				</p>
				<NcSelect :value.sync="getQuota"
					aria-label-combobox="set quota"
					class="quota-input"
					:clear-search-on-select="false"
					:taggable="true"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:multiple="false"
					:clearable="false"
					:options="['1 GB', '5 GB', '10 GB', t('workspace','unlimited')]"
					@option:selected="updateQuota" />
				<p class="max-contrast"
					v-html="getQuotaMessage" />
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
import NcButton from '@nextcloud/vue/components/NcButton'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
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

			if (res >= 100) {
				return 100
			}

			return 1
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
			return t('workspace', 'You use <b>{size}</b> on {quota}', { size: this.getSize, quota: this.getQuota })
		},
		getSpaceName() {
			return this.$store.getters.getSpaceByNameOrId(this.$route.params.space).name
		},
	},
	beforeMount() {
		const space = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
		this.color = space.color
		this.quota = space.quota
		this.size = space.size
		this.spacename = space.name
	},
	methods: {
		close() {
			this.$emit('close')
		},
		async save() {
			const oldSpace = this.$store.getters.getSpaceByNameOrId(this.$route.params.space)
			const oldSpaceName = oldSpace.name
			const space = { ...oldSpace }

			if (this.color !== space.color) {
				axios.post(generateUrl(`/apps/workspace/workspaces/${oldSpace.id}/color`),
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
				let responseRename = await renameSpace(oldSpace.id, this.spacename)
				responseRename = responseRename.data

				if (responseRename.statuscode === 204) {
					const spaceBeforeRenamed = { ...oldSpace }
					spaceBeforeRenamed.name = responseRename.space
					space.name = responseRename.space

					this.$store.dispatch('updateSpace', {
						space: spaceBeforeRenamed,
					})
					this.$store.dispatch('removeSpace', {
						space: oldSpace,
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
					path: `/workspace/${space.id}`,
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

			quota = quota.replace(' ', '')

			const control = new RegExp(`^(${t('workspace', 'unlimited')}|\\d+(tb|gb|mb|kb)?)$`, 'i')
			if (!control.test(quota)) {
				const text = t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota')
				showNotificationError('Error', text, 3000)
				return
			}

			quota = this.$store.getters.convertQuotaToByte(quota)

			this.quota = quota
		},
		updateSpacename(spacename) {
			this.spacename = spacename
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
	margin-top: 24px;
	margin-bottom: 12px;
}

.input-spacename :deep(div input) {
	width: 90%;
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
.max-contrast {
	color: var(--color-text-maxcontrast);
}

span {
	font-weight: bold;
}
</style>
