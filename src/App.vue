<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<Content id="content" app-name="workspace">
		<AppNavigation>
			<AppNavigationNewItem
				icon="icon-add"
				title="New space"
				@new-item="onNewSpace" />
			<AppNavigationItem v-for="space in spaces"
				:key="space.name"
				:title="space.name"
				@click="onOpenSpace(space)" />
		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<table v-if="selectedSpace === undefined">
					<thead>
						<tr>
							<th>Workspace name</th>
							<th>Administrator</th>
							<th>Quota</th>
						</tr>
					</thead>
					<tr v-for="space in spaces"
						:key="space.name">
						<td> {{ space.name }} </td>
						<td> {{ adminUsers(space).join(', ') }} </td>
						<td> {{ space.quota }} </td>
					</tr>
				</table>
				<div v-else>
					<table>
						<thead>
							<tr>
								<th>User</th>
								<th>Role</th>
								<th>Email</th>
								<th />
							</tr>
						</thead>
						<tr v-for="user in selectedSpace.users"
							:key="user.name">
							<td> {{ user.name }} </td>
							<td> {{ user.role }} </td>
							<td> {{ user.email }} </td>
							<td>
								<Actions>
									<ActionButton
										icon="icon-delete"
										@click="deleteUser">
										Delete user
									</ActionButton>
									<ActionButton
										icon="icon-user"
										@click="setUserAdmin">
										Make administrator
									</ActionButton>
								</Actions>
							</td>
						</tr>
					</table>
				</div>
			</AppContentDetails>
		</AppContent>
	</Content>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Content from '@nextcloud/vue/dist/Components/Content'

export default {
	name: 'App',
	components: {
		Actions,
		ActionButton,
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
	},
	data() {
		// TODO: spaces should be retrieved from groupfolders' API
		return {
			selectedSpace: undefined,
			spaces: [
				{
					name: 'spaceA',
					users: [
						{
							name: 'cyrille',
							role: 'admin',
							email: 'cyrille@bollu.be',
						},
						{
							name: 'dorianne',
							role: 'user',
							email: 'dorianne@arawa.fr',
						},
					],
					quota: '',
				},
				{
					name: 'spaceB',
					users: [
						{
							name: 'cyrille',
							role: 'admin',
							email: 'cyrille@bollu.be',
						},
						{
							name: 'baptiste',
							role: 'admin',
							email: 'baptiste@arawa.fr',
						},
						{
							name: 'dorianne',
							role: 'user',
							email: 'dorianne@arawa.fr',
						},
					],
					quota: '10GB',
				},
			],
		}
	},
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			// eslint-disable-next-line
			console.log('space')
			return space.users.filter((u) => u.role === 'admin').map((u) => u.name)
		},
		onNewSpace() {
			// TODO
		},
		onOpenSpace(space) {
			this.selectedSpace = space
		},
	},
}
</script>

<style scoped>

.app-navigation {
	display: block;
}

table {
	width: 100%;
}
tr:hover {
	background-color: #f5f5f5;
}

</style>
