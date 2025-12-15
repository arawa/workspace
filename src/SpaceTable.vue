<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
	@author 2021 Cyrille Bollu <cyrille@bollu.be>

	@license GNU AGPL version 3 or any later version

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div class="main-div">
		<div class="header" />
		<div v-if="Object.keys($store.state.spaces).length"
			class="table-container">
			<table class="table-spaces">
				<thead>
					<tr class="">
						<th class="workspace-th" />
						<th class="workspace-th">
							{{ t('workspace', 'Workspace name') }}
						</th>
						<th class="workspace-th">
							{{ t('workspace', 'Quota') }}
						</th>
						<th class="workspace-th workspace-managers-th">
							{{ t('workspace', 'Workspace Managers') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(space,name) in $store.state.spaces"
						:key="'space-item-' + space.id"
						class="workspace-tr"
						@click="openSpace(space.id)">
						<td style="width: 50px;" class="workspace-td">
							<span class="color-dot-home" :style="{background: space.color}" />
						</td>
						<td class="workspace-td">
							{{ name }}
						</td>
						<td class="workspace-td">
							{{ getQuota(space.quota) }}
						</td>
						<td class="workspace-td">
							<VueLazyComponent
								:key="'avatar-'+name"
								class="admin-avatars"
								@init="initAdmins(space.id, name)">
								<div class="container-avatars">
									<NcAvatar v-for="user in getFirstTenWorkspaceManagerUsers(space.name)"
										:key="user.uid"
										:style="{ marginRight: 2 + 'px' }"
										:display-name="user.name"
										:disable-menu="true"
										:show-user-status="false"
										:user="user.uid" />
									<div v-if="workspaceManagers(space).length > 10"
										v-tooltip="{
											content: getLatestWorkspaceManagerUsers(space.name),
											show: true,
										}"
										class="bubble-more-users">
										+{{ countWorkspaceManagerUsersAboveThreshold(space.name) }}
									</div>
								</div>
							</VueLazyComponent>
						</td>
					</tr>
				</tbody>
			</table>
			<PageLoader v-if="nextPage"
				v-element-visibility="next"
				:message="messageLoader" />
		</div>
		<WorkspaceContentEmpty v-if="noWorkspaceForSearch"
			:message="t('workspace', 'No workspaces found')" />
		<WorkspaceContentEmpty v-else-if="hasNoWorkspaces"
			:message="t('workspace', 'You have not yet created any workspace')" />
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { component as VueLazyComponent } from '@xunlei/vue-lazy-component'
import PageLoader from './components/PageLoader.vue'
import { WorkspacesLoader } from './mixins/WorkspacesLoader.mixin.js'
import WorkspaceContentEmpty from './components/Content/Empty/WorkspaceContentEmpty.vue'

export default {
	name: 'SpaceTable',
	components: {
		NcAvatar,
		VueLazyComponent,
		PageLoader,
		WorkspaceContentEmpty,
	},
	mixins: [WorkspacesLoader],
	computed: {
		noWorkspaceForSearch() {
			return this.$store.state.countTotalWorkspaces === 0
				&& (this.$store.state.searchWorkspace !== null && this.$store.state.searchWorkspace !== '')
		},
		hasNoWorkspaces() {
			return this.$store.state.countTotalWorkspaces === 0
		},
	},
	beforeMount() {
		this.showNextPage()
	},
	methods: {
		getQuota(quota) {
			return this.$store.getters.convertQuotaForFrontend(quota)
		},
		// Returns all workspace's managers
		workspaceManagers(space) {
			if (space.managers) {
				return Object.values(space.managers)
			}
			return Object.values(space.users).filter((u) => this.$store.getters.isSpaceAdmin(u, space))
		},
		openSpace(id) {
			this.$store.getters.getSpaceByNameOrId(id).isOpen = true
			this.$router.push({
				path: `/workspace/${id}`,
			})
		},
		initAdmins(id, name) {
			const space = this.$store.getters.getSpaceById(id)
			if (space !== null) {
				if (space.managers !== null || space.users.length > 0) {
					return
				}
				this.$store.dispatch('loadAdmins', space)
			}
		},
		getFirstTenWorkspaceManagerUsers(spacename) {
			return this.$store.getters.getFirstTenWorkspaceManagerUsers(spacename)
		},
		getLatestWorkspaceManagerUsers(spacename) {
			return this.$store.getters.getLatestWorkspaceManagerUsers(spacename)
		},
		countWorkspaceManagerUsersAboveThreshold(spacename) {
			return this.$store.getters.countWorkspaceManagerUsersAboveThreshold(spacename)
		},
	},
}
</script>

<style>
.admin-avatars {
	display: flex;
}

.container-avatars {
	display: flex;
	flex-direction: row;
}

.bubble-more-users {
	height: 32px;
	width: 32px;
	border-radius: 50%;
	background-color: var(--color-primary-light);
	display: flex;
	align-items: center;
	justify-content: center;
	color: var(--color-primary);
}

.color-dot-home {
	height: 35px;
	width: 35px;
	border-radius: 50%;
	display: block;
}

.table-container, .table-spaces {
	width: 100%;
}

.main-div {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: space-between;
}

td, td div {
	cursor: pointer;
}

.workspace-managers-th {
	text-align: left;
}
</style>
