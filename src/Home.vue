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
			close-on-click="true" />
		<AppNavigation>
			<AppNavigationNewItem v-if="$root.$data.isUserGeneralAdmin === 'true'"
				icon="icon-add"
				:title="t('workspace', 'New space')"
				@new-item="createSpace" />
			<AppNavigationItem
				:title="t('workspace', 'All spaces')"
				:to="{path: '/'}" />
			<AppNavigationItem v-for="(space, spaceName) in $store.state.spaces"
				:key="space.id"
				:class="$route.params.space === spaceName ? 'space-selected' : ''"
				:allow-collapse="true"
				:open="space.isOpen"
				:title="spaceName"
				:to="{path: `/workspace/${spaceName}`}">
				<AppNavigationIconBullet slot="icon" :color="space.color" />
				<CounterBubble slot="counter" class="user-counter">
					{{ $store.getters.spaceUserCount(spaceName) }}
				</CounterBubble>
				<div>
					<AppNavigationItem v-for="group in sortedGroups(Object.values(space.groups), spaceName)"
						:key="group.gid"
						icon="icon-group"
						:to="{path: `/group/${spaceName}/${group.gid}`}"
						:title="group.displayName">
						<CounterBubble slot="counter" class="user-counter">
							{{ $store.getters.groupUserCount( spaceName, group.gid) }}
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
import { getLocale } from '@nextcloud/l10n'

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
			axios.post(generateUrl('/apps/workspace/spaces'), { spaceName: name })
				.then(resp => {
					if (resp.data.statuscode !== 200 && resp.data.statuscode !== 201) {
						this.$notify({
							title: t('workspace', 'Error - Creating space'),
							text: t('workspace', 'This space or groupfolder already exist. Please, input another space.\nIf "toto" space exist, you cannot create the "tOTo" space.\nMake sure you the groupfolder doesn\'t exist.'),
							type: 'error',
						})
					} else {
						this.$store.commit('addSpace', {
							color: resp.data.color,
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
				.catch((e) => {
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured while trying to create the workspaces.') + '<br>' + t('workspace', 'The error is: ') + e,
						type: 'error',
					})
				})
		},
		// Sorts groups alphabeticaly
		sortedGroups(groups, space) {
			groups.sort((a, b) => {
				// Makes sure the GE- group is first in the list
				// These tests must happen before the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(space)
				if (a === GEGroup) {
					return -1
				}
				if (b === GEGroup) {
					return 1
				}
				// Makes sure the U- group is second in the list
				// These tests must be done after the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(space)
				if (a === UGroup) {
					return -1
				}
				if (b === UGroup) {
					return 1
				}
				// Normal locale based sort
				// Some javascript engines don't support localCompare's locales
				// and options arguments.
				// This is especially the case of the mocha test framework
				try {
					return a.displayName.localeCompare(b.displayName, getLocale(), {
						sensitivity: 'base',
						ignorePunctuation: true,
					})
				} catch (e) {
					return a.displayName.localeCompare(b.displayName)
				}
			})

			return groups
		},
	},
}
</script>

<style scoped>

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
