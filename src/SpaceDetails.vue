<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<div id="space-header">
			<span>
				{{ space.name }}
			</span>
		</div>
		<div id="space-details">
			<table>
				<thead>
					<tr>
						<th>{{ t('workspace', 'Users') }}</th>
						<th>{{ t('workspace', 'Role') }}</th>
						<th>{{ t('workspace', 'Email') }}</th>
						<th />
					</tr>
				</thead>
				<tbody>
					<tr v-for="user in space.users"
						:key="user.name">
						<td> {{ user.name }} </td>
						<td> {{ t('workspace', user.role) }} </td>
						<td> {{ user.email }} </td>
						<td>
							<Actions>
								<ActionButton
									icon="icon-delete"
									@click="deleteUser">
									{{ t('worksapce', 'Delete user') }}
								</ActionButton>
								<ActionButton
									icon="icon-user"
									@click="setUserAdmin">
									{{ t('workspace', 'Make administrator') }}
								</ActionButton>
							</Actions>
						</td>
					</tr>
					<tr>
						<td>
							<Multiselect
								id="newUser"
								v-model="selectedUsers"
								:options="selectableUsers"
								:label="displayName"
								:multiple="true"
								:placeholder="t('workspace', 'Select new user')"
								@search-change="lookupUsers" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
		Multiselect,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			selectedUsers: [],
			selectableUsers: [],
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
		deleteUser() {
			// TODO
		},
		setUserAdmin() {
			// TODO
		},
	},
}
</script>
