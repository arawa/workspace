<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<div class="header">
			<div class="space-name">
				<ColorPicker v-model="$store.state.spaces[$route.params.space].color" class="space-color-picker" @input="updateColor">
					<button class="color-dot color-picker" :style="{backgroundColor: $store.state.spaces[$route.params.space].color}" />
				</ColorPicker>
				<span class="titles-for-space">
					{{ title }}
				</span>
				<Multiselect
					:class="isESR ? 'quota-select-esr' : 'quota-select'"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:taggable="true"
					:value="$store.state.spaces[$route.params.space].quota"
					:options="['1GB', '5GB', '10GB', 'unlimited']"
					@change="setSpaceQuota"
					@tag="setSpaceQuota" />
			</div>
			<div class="space-actions">
				<div>
					<Actions default-icon="icon-add">
						<ActionButton
							icon="icon-user"
							:close-after-click="true"
							:title="t('workspace', 'Add users')"
							@click="toggleShowSelectUsersModal" />
						<ActionButton v-show="!createGroup"
							icon="icon-group"
							:title="t('workspace', 'Create group')"
							@click="toggleCreateGroup" />
						<ActionInput v-show="createGroup"
							ref="createGroupInput"
							icon="icon-group"
							:close-after-click="true"
							@submit="onNewGroup">
							{{ t('workspace', 'Group name') }}
						</ActionInput>
					</Actions>
				</div>
				<Actions v-if="$root.$data.isUserGeneralAdmin === 'true'">
					<ActionButton v-show="!renameSpace"
						icon="icon-rename"
						:title="t('workspace', 'Rename space')"
						@click="toggleRenameSpace" />
					<ActionInput v-show="renameSpace"
						ref="renameSpaceInput"
						icon="icon-rename"
						@submit="onSpaceRename">
						{{ t('workspace', 'Space name') }}
					</ActionInput>
					<ActionButton
						icon="icon-delete"
						:close-after-click="true"
						@click="deleteSpace">
						{{ t('workspace', 'Delete space') }}
					</ActionButton>
				</Actions>
			</div>
		</div>
		<UserTable :space-name="$route.params.space" />
		<Modal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<SelectUsers :space-name="$route.params.space" @close="toggleShowSelectUsersModal" />
		</Modal>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ColorPicker from '@nextcloud/vue/dist/Components/ColorPicker'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'
import UserTable from './UserTable'

export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
		ActionInput,
		ColorPicker,
		Modal,
		Multiselect,
		SelectUsers,
		UserTable,
	},
	data() {
		return {
			createGroup: false, // true to display 'Create Group' ActionInput
			renameSpace: false, // true to display 'Rename space' ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
			isESR: false,
		}
	},
	computed: {
		// The title to display at the top of the page
		title() {
			return this.$route.params.space + ' [ID: ' + this.$store.state.spaces[this.$route.params.space].id + ']'
		},
	},
	created() {
		const version = navigator.userAgent.split('Firefox/')[1]
		if (parseInt(version) < 91) {
			this.isESR = true
		}
	},
	methods: {
		// Deletes a space
		deleteSpace() {
			const space = this.$route.params.space

			const res = window.confirm(t('workspace', 'Are you sure you want to delete the {space} space ?', { space }))

			if (res) {
				axios.delete(generateUrl(`/apps/workspace/spaces/${this.$store.state.spaces[space].id}`))
					.then(resp => {
						if (resp.data.http.statuscode === 200) {

							this.$store.dispatch('removeSpace', {
								space: this.$store.state.spaces[space],
							})

							this.$router.push({
								path: '/',
							})
						}
					})
			}
		},
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleCreateGroup()

			// Don't accept empty names
			const group = e.target[1].value
			if (!group) {
				return
			}

			// Creates group
			this.$store.dispatch('createGroup', { name: this.$route.params.space, group })
		},
		onSpaceRename(e) {
			// Hides ActionInput
			this.toggleRenameSpace()

			// TODO: Change : the key from $root.spaces, groupnames, change the route into new spacename because
			// the path is `https://instance-nc/apps/workspace/workspace/Aang`
			const oldSpaceName = this.$route.params.space
			axios.patch(generateUrl(`/apps/workspace/spaces/${this.$store.state.spaces[oldSpaceName].id}`),
				{
					newSpaceName: e.target[1].value,
				})
				.then(resp => {
					const data = resp.data
					if (data.statuscode === 204) {
						const space = { ...this.$store.state.spaces[oldSpaceName] }
						space.name = data.space
						space.groups = data.groups
						this.$store.dispatch('updateSpace', {
							space,
						})
						this.$store.dispatch('removeSpace', {
							space: this.$store.state.spaces[oldSpaceName],
						})
						this.$router.push({
							path: `/workspace/${space.name}`,
						})
					}

					if (data.statuscode === 401) {
						// TODO: May be to print an error message temporary
						console.error(data.message)
					}
				})
		},
		// Sets a space's quota
		setSpaceQuota(quota) {
			if (quota === null) {
				return
			}
			const control = /^(unlimited|\d+(tb|gb|mb|kb)?)$/i
			if (!control.test(quota)) {
				this.$notify({
					title: t('workspace', 'Error'),
					text: t('workspace', 'You may only specify "unlimited" or a number followed by "TB", "GB", "MB", or "KB" (eg: "5GB") as quota'),
					type: 'error',
				})
				return
			}
			this.$store.dispatch('setSpaceQuota', {
				name: this.$route.params.space,
				quota,
			})
		},
		toggleCreateGroup() {
			this.createGroup = !this.createGroup
			if (this.createGroup === true) {
				this.$refs.createGroupInput.$el.focus()
			}
		},
		toggleRenameSpace() {
			this.renameSpace = !this.renameSpace
			if (this.renameSpace === true) {
				this.$refs.renameSpaceInput.$el.focus()
			}
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
		updateColor(e) {
			const spacename = this.$route.params.space
			axios.post(generateUrl(`/apps/workspace/workspaces/${this.$store.state.spaces[spacename].id}/color`),
				{
					colorCode: e,
				})
				.then(resp => {
					this.$store.dispatch('updateColor', {
						name: spacename,
						colorCode: e,
					})
				})
				.catch(err => {
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured when trying to change the workspace\'s color.') + '<br>' + t('workspace', 'The error is: ') + err,
						type: 'error',
					})
				})
		},
	},
}
</script>

<style>
.space-actions,
.space-color-picker,
.space-name,
.user-actions {
	display: flex;
}

.color-picker {
	margin: 0px;
}

.quota-select {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
}

.quota-select-esr {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100% !important;
}

.space-color-picker {
	margin-right: 8px;
}

.space-name {
	margin-left: 8px;
	margin-top: -40px;
}

.user-actions {
	flex-flow: row-reverse;
}

</style>
