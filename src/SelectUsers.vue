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
			id="newUser"
			v-model="selectedUsers"
			:options="selectableUsers"
			:label="displayName"
			:multiple="true"
			:placeholder="t('workspace', 'Select new user')"
			@search-change="lookupUsers" />
		<div class="select-users-list">
			<div v-for="user in allSelectedUsers"
				:key="user.name">
				<span> {{ user.name }} </span>
			</div>
		</div>
		<div class="select-users-actions">
			<Actions>
				<ActionButton
					icon="icon-add"
					@click="addUsers">
					{{ t('worksapce', 'Add users') }}
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
			selectedUsers: [], // Users selected in a search
			selectableUsers: [], // Users matching a search term
		}
	},
	methods: {
		lookupUsers(term) {
			// safeguard for initialisation
			if (term === undefined || term === '') {
				return
			}

			// TODO: Users must be filtered to only those groups used in this EP
			// TODO: limit max results?
			axios.get(
				generateUrl('/apps/workspace/api/autoComplete/{term}', { term })
			)
				.then((resp) => {
					this.selectableUsers = resp.data
					// eslint-disable-next-line
					console.log(this.selectableUsers)
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
.select-users-wrapper {
	margin: 10px;
}
.select-users-list {
	min-height: 400px;
	max-height: 400px;
	min-width: 500px;
}
</style>
