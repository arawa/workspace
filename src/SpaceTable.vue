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
		<table v-if="Object.keys($store.state.spaces).length" class="table-spaces">
			<thead>
				<tr class="">
					<th class="workspace-th" />
					<th class="workspace-th">
						{{ t('workspace', 'Workspace name') }}
					</th>
					<th class="workspace-th">
						{{ t('workspace', 'Quota') }}
					</th>
					<th class="workspace-th">
						{{ t('workspace', 'Space administrators') }}
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
							<NcAvatar v-for="user in workspaceManagers(space)"
								:key="user.uid"
								:style="{ marginRight: 2 + 'px' }"
								:display-name="user.name"
								:show-user-status="false"
								:user="user.uid" />
						</VueLazyComponent>
					</td>
				</tr>
			</tbody>
		</table>
		<NcEmptyContent v-else
			:name="t('workspace', 'No spaces')">
			<template #icon>
				<NcIconSvgWrapper name="folders-off" :path="mdiFolderOff" />
			</template>
			<template #description>
				{{ t('workspace', 'You have not yet created any workspace') }}
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import { component as VueLazyComponent } from '@xunlei/vue-lazy-component'
import { mdiFolderOff } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

export default {
	name: 'SpaceTable',
	components: {
		NcAvatar,
		NcEmptyContent,
		NcIconSvgWrapper,
		VueLazyComponent,
	},
	data() {
		return {
			mdiFolderOff,
		}
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
	},
}
</script>

<style>
.admin-avatars {
	display: flex;
	flex-flow: row-reverse;
}

.color-dot-home {
	height: 35px;
	width: 35px;
	border-radius: 50%;
	display: block;
}

.table-spaces {
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

</style>
