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
				<span class="space-title">
					{{ $route.params.space }}
				</span>
				<Multiselect
					class="quota-select"
					:disabled="$root.$data.isUserGeneralAdmin === 'false'"
					:placeholder="t('workspace', 'Set quota')"
					:taggable="true"
					:value="$root.$data.spaces[$route.params.space].quota"
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
					<ActionButton
						icon="icon-rename"
						@click="renameSpace">
						{{ t('workspace', 'Rename space') }}
					</ActionButton>
					<ActionButton
						icon="icon-delete"
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
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'
import UserTable from './UserTable'
import Vue from 'vue'

export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
		ActionInput,
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
	methods: {
		deleteSpace() {
			// TODO
		},
		// Creates a group and navigates to its details page
		onNewGroup(e) {
			// Hides ActionInput
			this.toggleCreateGroup()
			// Don't accept empty names
			const group = e.target[1].value
			if (!group) {
				return
			}

			// Creates group in frontend
			const space = this.$root.$data.spaces[this.$route.params.space]
			const oldGroups = space.groups
			space.groups[group] = group
			Vue.set(this.$root.$data.spaces, this.$route.params.space, space)

			// Creates group in backend
			axios.post(generateUrl(`/apps/workspace/group/add/${group}`))
				.then((resp) => {
					// Give group access to space
					axios.post(generateUrl(`/apps/groupfolders/folders/${space.id}/groups`), { group })
						.then((resp) => {
							// Navigates to the group's details page
							this.$root.$data.spaces[this.$route.params.space].isOpen = true
							this.$router.push({
								path: `/group/${space.name}/${group}`,
							})
						})
						.catch((e) => {
							// TODO revert frontend change, delete group in backend, inform user
						})
				})
				.catch((e) => {
					space.groups = oldGroups
					Vue.set(this.$root.$data.spaces, this.$route.params.space, space)
					// TODO Inform user
				})
		},
		renameSpace() {
			// TODO
		},
		// Sets a space's quota
		setSpaceQuota(quota) {
			// Controls quota
			const control = /^(unlimited|\d+(tb|gb|mb|kb)?)$/i
			if (!control.test(quota)) {
				return
				// TODO inform user
			}

			// Updates frontend
			const space = this.$root.$data.spaces[this.$route.params.space]
			const oldQuota = space.quota
			space.quota = quota
			Vue.set(this.$root.$data.spaces, this.$route.params.space, space)

			// Transforms quota for backend
			switch (quota.substr(-2).toLowerCase()) {
			case 'tb':
				quota = quota.substr(0, quota.length - 2) * 1024 ** 4
				break
			case 'gb':
				quota = quota.substr(0, quota.length - 2) * 1024 ** 3
				break
			case 'mb':
				quota = quota.substr(0, quota.length - 2) * 1024 ** 2
				break
			case 'kb':
				quota = quota.substr(0, quota.length - 2) * 1024
				break
			}
			quota = (quota === 'unlimited') ? -3 : quota

			// Updates backend
			const url = generateUrl(`/apps/groupfolders/folders/${space.id}/quota`)
			axios.post(url, { quota })
				.catch((e) => {
					// Reverts change made in the frontend in case of error
					space.quota = oldQuota
					Vue.set(this.$root.$data.spaces, this.$route.params.space, space)
					// TODO Inform user
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
.space-name,
.user-actions {
	display: flex;
}

.user-actions {
	flex-flow: row-reverse;
}

.quota-select {
	margin-left: 20px !important;
	min-width: 100px;
	max-width: 100px;
}

.space-title {
	font-weight: bold;
	font-size: xxx-large;
}

.user-admin {
	background-color: #F7FBFE;
}

.user-name {
	font-size: large;
}

.user-email {
	color: gray;
	padding-left: 10px;
}
</style>
