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
						<td> {{ user.role }} </td>
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
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
export default {
	name: 'SpaceDetails',
	components: {
		Actions,
		ActionButton,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
	},
	methods: {
		deleteUser() {
			// TODO
		},
		setUserAdmin() {
			// TODO
		},
	},
}
</script>
