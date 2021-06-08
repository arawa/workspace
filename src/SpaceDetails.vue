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
// import Vue from 'vue'

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
			this.$store.addGroupToSpace(this.$route.params.space, group)

			// Creates group in backend
			axios.post(generateUrl(`/apps/workspace/group/add/${group}`))
				.then((resp) => {
					// Give group access to space
					axios.post(generateUrl(`/apps/groupfolders/folders/${this.$route.params.space}/groups`), { group })
						.then((resp) => {
							// Navigates to the group's details page
							this.$store.state.spaces[this.$route.params.space].isOpen = true
							this.$router.push({
								path: `/group/${this.$route.params.space}/${group}`,
							})
						})
						.catch((e) => {
							// TODO revert frontend change, delete group in backend, inform user
						})
				})
				.catch((e) => {
					this.$store.removeGroupFromSpace(this.$route.params.space, group)
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
					newSpaceName: e.target[1].value
				})
				.then(resp => {
					const data = resp.data

					if (data.statuscode === 204) {
						const space = { ...this.$store.state.spaces[oldSpaceName] }
						space.name = data.space
						this.$store.dispatch('updateSpace', {
							space
						})
						this.$store.dispatch('removeSpace', {
							space: this.$store.state.spaces[oldSpaceName]
						})
						this.$router.push({
							path: `/workspace/${space.name}`,
						})
					}
				})
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
			const oldQuota = this.$store.state.spaces[this.$route.params.space].quota
			this.$store.setSpaceQuota(this.$route.params.space, quota)

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
			const url = generateUrl(`/apps/groupfolders/folders/${this.$route.params.space}/quota`)
			axios.post(url, { quota })
				.catch((e) => {
					// Reverts change made in the frontend in case of error
					this.$store.setSpaceQuota(this.$route.params.space, oldQuota)
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
