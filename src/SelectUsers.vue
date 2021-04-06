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
			label="displayName"
			:options="selectableUsers"
			:loading="isLookingUpUsers"
			:placeholder="t('workspace', 'Select new user')"
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
					:key="user.displayName"
					class="user-entry">
					<div>
						<span> {{ user.displayName }} </span>
					</div>
					<div class="user-entry-actions">
						<input type="checkbox" class="role-toggle" @change="toggleUserRole" />
						<Actions>
							<ActionButton
								icon="icon-delete"
								@click="removeUserFromBatch">
								{{ t('workspace', 'remove users from selection') }}
							</ActionButton>
						</Actions>
					</div>
				</div>
			</div>
		</div>
		<div class="select-users-actions">
			<Actions>
				<ActionButton
					icon="icon-add"
					@click="addUsersToWorkspace">
					{{ t('workspace', 'Add users') }}
				</ActionButton>
			</Actions>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'SelectUsers',
	components: {
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
		// Adds users to the batch when user selects users in the MultiSelect
		addUsersToBatch(selectedUsers) {
			this.allSelectedUsers.push(selectedUsers)
		},
		// Lookups users in NC directory when user types text in the MultiSelect
		lookupUsers(term) {
			// safeguard for initialisation
			if (term === undefined || term === '') {
				return
			}

			// TODO: Users must be filtered to only those groups used in this EP
			// TODO: limit max results?
			this.isLookingUpUsers = true
			axios.get(
				generateUrl('/apps/workspace/api/autoComplete/{term}', { term })
			)
				.then((resp) => {
					this.selectableUsers = resp.data
					this.isLookingUpUsers = false
				})
				.catch((e) => {
					// TODO: add some user feedback
					this.isLookingUpUsers = false
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
</style>
