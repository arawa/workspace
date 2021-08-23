<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div class="select-users-wrapper">
		<Actions class="action-close">
			<ActionButton
				icon="icon-close"
				@click="$emit('close')" />
		</Actions>
		<Multiselect
			v-model="selectedUsers"
			class="select-users-input"
			label="name"
			track-by="uid"
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
					class="user-entry"
					:class="$store.getters.isMember($route.params.space, user) ? '' : 'user-not-member'">
					<div>
						<div class="icon-member" :class="$store.getters.isMember($route.params.space, user) ? 'is-member' : ''" />
						<Avatar :display-name="user.name" :user="user.name" />
						<div class="user-name">
							<span> {{ user.name }} </span>
						</div>
					</div>
					<div class="user-entry-actions">
						<div v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)">
							<input type="checkbox" class="role-toggle" @change="toggleUserRole(user)">
							<label>{{ t('workspace', 'S.A.') }}</label>
						</div>
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
		<p v-if="$route.params.group && addingUsersToWorkspace" class="caution">
			{{ t('workspace', 'Caution, users highlighted in red are not yet member of this workspace. They will be automaticaly added.') }}
		</p>
		<div class="select-users-actions">
			<button @click="addUsersToWorkspaceOrGroup">
				{{ t('workspace', 'Add users') }}
			</button>
		</div>
	</div>
</template>

<script>
import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from './constants'
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
	computed: {
		// Returns true when at least 1 selected user is not yet member of the workspace
		addingUsersToWorkspace() {
			return !this.allSelectedUsers.every(user => {
				return this.$store.getters.isMember(this.$route.params.space, user)
			})
		},
	},
	created() {
		// This test makes sure this.lookupUsers() is not called during unit tests
		if (this.$route.params.space !== undefined) {
			this.lookupUsers('*')
		}
	},
	methods: {
		// Adds users to workspace/group and close dialog
		// In the end, it always boils down to adding the user to a group
		// NOTE that the backend takes care of adding the user to the U- group, and Workspace managers
		// group if needed.
		// CAUTION, we are not giving a gid here but rather a group's displayName
		// (the space's name, in this.$route.params.space can change).
		// This should however be handled in the backend
		// IMPROVEMENT POSSIBLE: I think the backend nows store the real GID of
		// the U- and GE- groups in some specific attribute of the space object.
		// We might use them here.
		addUsersToWorkspaceOrGroup() {
			this.$emit('close')
			const spaceId = this.$store.state.spaces[this.$route.params.space].id
			this.allSelectedUsers.forEach(user => {
				let gid = ''
				if (this.$route.params.group !== undefined) {
					// Adding a user to a workspace 'subgroup
					this.$store.dispatch('addUserToGroup', {
						name: this.$route.params.space,
						gid: this.$route.params.group,
						user,
					})
					if (user.role === 'admin') {
						this.$store.dispatch('addUserToGroup', {
							name: this.$route.params.space,
							gid: ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + spaceId,
							user,
						})
					}
				} else {
					// Adding a user to the workspace
					gid = user.role === 'admin' ? ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + spaceId : ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + spaceId
					this.$store.dispatch('addUserToGroup', {
						name: this.$route.params.space,
						gid,
						user,
					})
				}
			})
		},
		// Adds users to the batch when user selects users in the MultiSelect
		addUsersToBatch(users) {
			this.allSelectedUsers = users
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
				spaceId: this.$store.state.spaces[this.$route.params.space].id,
			}))
				.then((resp) => {
					let users = []
					if (resp.status === 200) {
						// When adding users to a space, show only those users who are not already member of the space
						if (this.$route.params.group === undefined) {
							const space = this.$store.state.spaces[this.$route.params.space]
							users = resp.data.filter(user => {
								return (!(user.uid in space.users))
							}, space)
						} else {
							users = resp.data
						}
						// Filters user that are already selected
						this.selectableUsers = users.filter(newUser => {
							return this.allSelectedUsers.every(user => {
								return newUser.uid !== user.uid
							})
						})
					} else {
						this.$notify({
							title: t('workspace', 'Error'),
							text: t('workspace', 'An error occured while trying to lookup users.') + '<br>' + t('workspace', 'The error is: ') + resp.statusText,
							type: 'error',
						})
					}
				})
				.catch((e) => {
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured while trying to lookup users.') + '<br>' + t('workspace', 'The error is: ') + e,
						type: 'error',
					})
				})
			this.isLookingUpUsers = false
		},
		removeUserFromBatch(user) {
			this.selectedUsers = this.selectedUsers.filter((u) => {
				return u.name !== user.name
			})
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
.action-close {
	align-self: self-end;
	width: 90%;
}

.caution {
	color: red;
	margin: 5px;
	width: 90%;
}

.icon-member {
	position: relative;
	left: 10px;
	top: -10px;
	z-index: 10;
	width: 20px;
	height: 20px;
}

.is-member {
	background-image: url('../img/member.png');
	background-repeat: no-repeat;
	background-position: center center;
	background-size: contain;
}

.select-users-actions {
	display: flex;
	flex-flow: row-reverse;
}

.select-users-input {
	width: 90%;
}

.select-users-list {
	min-height: 400px;
	max-height: 400px;
	min-width: 500px;
	margin-top: 5px;
	border-style: solid;
	border-width: 1px;
	border-color: #dbdbdb;
	width: 90%;
	overflow: scroll;
}

.select-users-list-empty {
	text-align: center;
	line-height: 400px;
	width: 90%;
}

.select-users-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin: 10px;
}

.user-entry {
	justify-content: space-between;
	padding-left: 5px;
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

.user-not-member {
	background-color: #ffebee
}

.role-toggle {
	cursor: pointer !important;
}
</style>
