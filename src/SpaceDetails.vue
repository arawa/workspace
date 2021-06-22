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
				<ColorPicker v-model="$store.state.spaces[$route.params.space].color" class="space-color-picker">
					<button class="color-dot color-picker" :style="{backgroundColor: $store.state.spaces[$route.params.space].color}" />
				</ColorPicker>
				<span class="space-title">
					{{ title }}
				</span>
				<Multiselect
					class="quota-select"
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
							@submit="onNewGroup">
							{{ t('workspace', 'Group name') }}
						</ActionInput>
					</Actions>
				</div>
				<Actions v-if="$root.$data.isUserGeneralAdmin === 'true'">
					<ActionInput
						icon="icon-rename"
						@submit="renameSpace">
						{{ t('workspace', 'Rename space') }}
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
// import Vue from 'vue'

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
			createGroup: false, // true to display ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	computed: {
		// The title to display at the top of the page
		title() {
			return this.$route.params.space + ' [ID: ' + this.$store.state.spaces[this.$route.params.space].id + ']'
		},
	},
	methods: {
		// Deletes a space
		deleteSpace() {
			const space = this.$route.params.space

			const res = window.confirm(`Are you sure you want to delete the ${space} space ?`)

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
		// Creates a group and navigates to its details page
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleCreateGroup()

			// Don't accept empty names
			let group = e.target[1].value
			if (!group) {
				return
			}

			// Groups must be postfixed with the ID of the space they belong
			const space = this.$store.state.spaces[this.$route.params.space]
			group = group + '-' + space.id

			// Creates group in frontend
			this.$store.commit('addGroupToSpace', { name: this.$route.params.space, group })

			// Creates group in backend
			axios.post(generateUrl(`/apps/workspace/api/group/${group}`), { spaceId: space.id })
				.then((resp) => {
					if (resp.status === 200) {
						// Navigates to the group's details page
						this.$store.state.spaces[this.$route.params.space].isOpen = true
						this.$router.push({
							path: `/group/${this.$route.params.space}/${group}`,
						})
					} else {
						this.$store.commit('removeGroupFromSpace', { name: this.$route.params.space, group })
						// TODO Inform user
					}
				})
				.catch((e) => {
					this.$store.commit('removeGroupFromSpace', { name: this.$route.params.space, group })
					// TODO Inform user
				})
		},
		renameSpace(e) {
			// TODO
			const oldSpaceName = this.$route.params.space

			// TODO: Change : the key from $root.spaces, groupnames, change the route into new spacename because
			// the path is `https://instance-nc/apps/workspace/workspace/Aang`
			axios.patch(generateUrl(`/apps/workspace/spaces/${this.$store.state.spaces[oldSpaceName].id}`),
				{
					newSpaceName: e.target[1].value,
				})
				.then(resp => {
					const data = resp.data

					if (data.statuscode === 204) {
						const space = { ...this.$store.state.spaces[oldSpaceName] }
						space.name = data.space
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
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
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

.quota-select {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
}

.space-color-picker {
	margin-right: 8px;
}

.space-title {
	font-weight: bold;
	font-size: xxx-large;
}

.user-actions {
	flex-flow: row-reverse;
}

.user-admin {
	background-color: #F7FBFE;
}

.user-email {
	color: gray;
	padding-left: 10px;
}

.user-name {
	font-size: large;
}
</style>
