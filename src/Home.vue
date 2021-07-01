<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<Content id="content" app-name="workspace">
		<notifications
			position="top center"
			width="50%"
			class="notifications"
			closeOnClick="true" />
		<AppNavigation>
			<AppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="createSpace" />
			<AppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}" />
			<AppNavigationItem v-for="(space, name) in $store.state.spaces"
				:key="name"
				:class="$route.params.space === name ? 'space-selected' : ''"
				:allow-collapse="true"
				:open="space.isOpen"
				:title="name"
				:to="{path: `/workspace/${name}`}">
				<AppNavigationIconBullet slot="icon" :color="space.color" />
				<CounterBubble slot="counter" class="user-counter">
					{{ $store.getters.spaceUserCount(name) }}
				</CounterBubble>
				<div>
					<AppNavigationItem v-for="group in Object.values(space.groups)"
						:key="group.gid"
						icon="icon-group"
						:to="{path: `/group/${name}/${group.gid}`}"
						:title="group.displayName">
						<CounterBubble slot="counter" class="user-counter">
							{{ $store.getters.groupUserCount( name, group.gid) }}
						</CounterBubble>
					</AppNavigationItem>
				</div>
			</AppNavigationItem>

		</AppNavigation>
		<AppContent>
			<AppContentDetails>
				<router-view />
			</AppContentDetails>
		</AppContent>
	</Content>
</template>

<script>
import axios from '@nextcloud/axios'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNewItem from '@nextcloud/vue/dist/Components/AppNavigationNewItem'
import Content from '@nextcloud/vue/dist/Components/Content'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Home',
	components: {
		AppContent,
		AppContentDetails,
		AppNavigation,
		AppNavigationIconBullet,
		AppNavigationItem,
		AppNavigationNewItem,
		Content,
	},
	created() {
		axios.get(generateUrl('/apps/workspace/spaces'))
			.then(resp => {
				if (resp.status !== 200) {
					this.$notify({
						title: t('workspace', 'Error'),
						text: t('workspace', 'An error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + resp.statusText,
						type: 'error',
					})
					return
				}

				// Initialises the store
				Object.values(resp.data).forEach(space => {
					let codeColor = space.color_code
					if (space.color_code === null) {
						codeColor = '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6)
					}
					this.$store.commit('addSpace', {
						// TODO color should be returned by backend
						color: codeColor,
						groups: space.groups,
						id: space.id,
						groupfolderId: space.groupfolder_id,
						isOpen: false,
						name: space.space_name,
						quota: this.convertQuotaForFrontend(space.quota),
						users: space.users,
					})
				})
			})
			.catch((e) => {
				this.$notify({
					title: t('workspace', 'Network error'),
					text: t('workspace', 'A network error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + e,
					type: 'error',
				})
			})
	},
	methods: {
		// Shows a space quota in a user-friendly way
		convertQuotaForFrontend(quota) {
			if (quota === '-3') {
				return 'unlimited'
			} else {
				const units = ['', 'KB', 'MB', 'GB', 'TB']
				let i = 0
				while (quota >= 1024) {
					quota = quota / 1024
					i++
				}
				return quota + units[i]
			}
		},
		// Creates a new space and navigates to its details page
		createSpace(name) {
			if (name === '') {
				this.$notify({
					title: t('workspace', 'Error'),
					text: t('workspace', 'Please specify a name.'),
					type: 'error',
				})
				return
			}
			axios.post(generateUrl('/apps/workspace/spaces'),
				{
					spaceName: name,
				}
			)
				.then(resp => {
					if (resp.data.statuscode !== 200 && resp.data.statuscode !== 201) {
						this.$notify({
							title: t('workspace', 'Error - Creating space'),
							text: t('workspace', 'This space or groupfolder already exist. Please, input another space.\nIf "toto" space exist, you cannot create the "tOTo" space.\nMake sure you the groupfolder doesn\'t exist.'),
							type: 'error'
						})
					} else {
						this.$store.commit('addSpace', {
							color: '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6),
							groups: resp.data.groups,
							isOpen: false,
							id: resp.data.id_space,
							name,
							quota: undefined,
							users: [],
						})
						this.$router.push({
							path: `/workspace/${name}`,
						})
					}
				})
				.catch(err => {
					console.error('Here')
					console.error(err)
				})
		},
	},
}
</script>

<style scoped>
.app-content-details {
	display: block;
	margin-left: auto;
	margin-right: auto;
	width: 80%;
}

.app-navigation {
	display: block;
}

.app-navigation-entry {
	padding-right: 0px;
}

.space-selected {
	background-color: #EAF5FC;
}

tr:hover {
	background-color: #f5f5f5;
}

.user-counter {
	margin-right: 5px;
}

.notifications {
	margin-top: 70px;
}
</style>
