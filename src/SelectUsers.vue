<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div class="select-users-wrapper">
		<Multiselect
			v-model="selectedUsers"
			class="select-users-input"
			label="name"
			:loading="isLookingUpUsers"
			:multiple="true"
			:options="selectableUsers"
			:placeholder="t('workspace', 'Start typing to lookup users')"
			:tag-width="50"
			:user-select="true"
			@change="addUsersToBatch"
			@search-change="lookupUsers" />
		<div class="select-users-list">
			<div v-if="allSelectedUsers.length === 0"
				class="select-users-list-empty">
				<span>
					{{ t('workspace', 'No users selected') }}
				</span>
			</div>
			<div v-else>
				<div v-for="user in allSelectedUsers"
					:key="user.name"
					class="user-entry">
					<div>
						<Avatar :display-name="user.name" :user="user.name" />
						<div class="user-name">
							<span> {{ user.name }} </span>
						</div>
					</div>
					<div class="user-entry-actions">
						<input type="checkbox" class="role-toggle" @change="toggleUserRole(user)">
						<label>{{ t('workspace', 'S.A.') }}</label>
						<Actions>
							<ActionButton
								icon="icon-delete"
								@click="removeUserFromBatch(user)">
								{{ t('workspace', 'remove users from selection') }}
							</ActionButton>
						</Actions>
					</div>
				</div>
			</div>
		</div>
		<div class="select-users-actions">
			<button @click="addUsersToWorkspace">
				{{ t('workspace', 'Add users') }}
			</button>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'SelectUsers',
	components: {
		Avatar,
		Actions,
		ActionButton,
		Multiselect,
	},
	data() {
		return {
			allSelectedUsers: [], // All selected users from all searches
			isLookingUpUsers: false, // True when we are looking up users
			selectedUsers: [], // Users selected in a search
			selectableUsers: [], // Users matching a search term
		}
	},
	methods: {
		// Adds users to workspace and close dialog
		addUsersToWorkspace() {
			// Update frontend first and keep a backup of the changes should something fail
			const spaceBackup = this.$store.state.spaces[this.$route.params.space]
			const space = this.$store.state.spaces[this.$route.params.space]
			this.allSelectedUsers.filter(user => user.role === 'user').forEach(user => {
				space.users[user.name] = user
			})
			this.allSelectedUsers.filter(user => user.role === 'admin').forEach(user => {
				space.admins[user.name] = user
			})
			this.$store.commit('updateSpace', space)
			this.$emit('close')

			// Update backend and revert frontend changes if something fails
			this.allSelectedUsers.forEach((user) => {
				// TODO Use application-wide constants
				let group = user.role === 'admin' ? 'GE-' : 'U-'
				group = group + this.$route.params.space

				// Add user to proper workspace group
				axios.patch(
					generateUrl('/apps/workspace/group/addUser/{space}', {
						space: this.$route.params.space,
					}),
					{
						group,
						user: user.name,
					}
				).then((resp) => {
					if (resp.status !== '204') {
						// TODO
					}
				}).catch((e) => {
					// TODO: Inform user
					this.$store.commit('addSpace', spaceBackup)
				})
			})
		},
		// Adds users to the batch when user selects users in the MultiSelect
		addUsersToBatch(user) {
			this.allSelectedUsers = [...new Set(this.allSelectedUsers.concat(user))]
		},
		// Lookups users in NC directory when user types text in the MultiSelect
		lookupUsers(term) {
			// safeguard for initialisation
			if (term === undefined || term === '') {
				return
			}

			// TODO: limit max results?
			this.isLookingUpUsers = true
			axios.get(generateUrl('/apps/workspace/api/autoComplete/{term}/{spaceId}', {
				term,
				spaceId: this.$route.params.space,
			}))
				.then((resp) => {
					const space = this.$store.state.spaces[this.$route.params.space]
					// Show only those users who are not already member of the space
					this.selectableUsers = resp.data.filter(user => {
						return (!(user.name in space.users) && !(user.name in space.admins))
					}, space)
					this.isLookingUpUsers = false
				})
				.catch((e) => {
					// TODO: add some user feedback
					this.isLookingUpUsers = false
				})
		},
		removeUserFromBatch(user) {
			this.allSelectedUsers = this.allSelectedUsers.filter((u) => {
				return u.name !== user.name
			})
		},
		toggleUserRole(user) {
			this.allSelectedUsers = this.allSelectedUsers.map(u => {
				if (u.name === user.name) {
					u.role = u.role === 'user' ? 'admin' : 'user'
					return u
				} else {
					return u
				}
			})
		},
	},
}
</script>

<style>
.select-users-actions {
	display: flex;
	flex-flow: row-reverse;
}

.select-users-input {
	width: 100%;
}

.select-users-list {
	min-height: 400px;
	max-height: 400px;
	min-width: 500px;
	margin-top: 5px;
	border-style: solid;
	border-width: 1px;
	border-color: #dbdbdb;
}

.select-users-list-empty {
	text-align: center;
	line-height: 400px;
}

.select-users-wrapper {
	margin: 10px;
}

.user-entry {
	justify-content: space-between;
	margin-left: 5px;
}

.user-entry,
.user-entry div {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.user-name {
	margin-left: 10px;
}

.role-toggle {
	cursor: pointer !important;
}
</style>
