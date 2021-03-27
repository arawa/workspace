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
// import debouncePromise from 'debounce-promise'
import { generateOcsUrl } from '@nextcloud/router'
import xmlToJSON from 'xmltojson'

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
			// fetch users using OCS API
			let url = generateOcsUrl('/core/autocomplete/get?search=' + term, 2)
			url = url.endsWith('/') ? url.slice(0, -1) : url
			fetch(url, {
				headers: { 'OCS-APIRequest': 'true' },
			})
				.then((resp) => resp.text())
				.then(text => {
					const data = xmlToJSON.parseString(text).ocs[0].data[0].element
					this.selectableUsers = data.map(user => {
						// eslint-disable-next-line
						console.log('user', user)
						return {
							label: user.label[0]._text
						}
					})
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
