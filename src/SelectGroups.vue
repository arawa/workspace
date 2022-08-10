<!--
  @copyright Copyright (c) 2017 Arawa

  @author 2022 Baptiste Fotia <baptiste.fotia@arawa.fr>

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
	<div class="content-select-groups">
		<div class="header-select-groups">
			<div class="header-select-groups-title">
				<h1 class="title-select-groups">
					{{ t('workspace', 'Select groups to import in this workspace') }}
				</h1>
			</div>
			<Actions class="action-close">
				<ActionButton
					icon="icon-close"
					@click="$emit('close')" />
			</Actions>
		</div>
		<div class="select-groups-list">
			<div
				v-for="(group) in $store.state.groups"
				:key="group.displayName"
				class="group-entry">
				<div class="group-select-name">
					<span>{{ group.displayName }}</span>
				</div>
				<input
					:id="group.displayName"
					v-model="allSelectedGroupsId"
					type="checkbox"
					:value="group.displayName"
					class="convert-space">
			</div>
		</div>
		<div class="select-groups-actions">
			<button @click="importGroupsToWorkspace()">
				{{ t('workspace', 'Import spaces') }}
			</button>
		</div>
	</div>
</template>

<script>

import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import {getAll} from './services/groupsService'
import { addGroup } from './services/groupfoldersService'
import { ESPACE_GID_PREFIX, ESPACE_USERS_PREFIX } from './constants'

export default {
	name: 'SelectGroups',
	components: {
		Actions,
		ActionButton,
	},
	data() {
		return {
			allSelectedGroupsId: [],
		}
	},
	created() {
		this.getGroups()
			.then(groups => {
				groups.forEach(group => {
					this.$store.dispatch('updateGroups', {
						group,
					})
				})
			})
			.catch(error => {
				console.error('Error to update the groups list.', error)
			})
	},
	methods: {
		async getGroups() {
			const groups = await getAll()
				.then(resp => {
					return resp
				})
				.catch(error => {
					return error
				})
			return groups
		},
		importGroupsToWorkspace() {
			const groupsBackup = this.$store.state.groups
			this.$emit('close')
			// space.groupfolderId
			const space = this.$store.state.spaces[this.$route.params.space]
			const groups = {}
			this.allSelectedGroupsId.forEach(gid => {
				groups[gid] = groupsBackup[gid]
			})
			for (const gid in groups) {
				// add group backend side
				addGroup(space.groupfolderId, groups[gid].gid)
					.then(res => {
						if (res.success) {
							// Add users from groups to U-X
							for (const uid in groups[gid].users) {
								const user = groups[gid].users[uid]
								this.$store.dispatch('addUserToGroup', {
									name: space.name,
									gid: ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.id,
									user,
								})
							}
							// add group frontend side
							this.$store.dispatch('ADD_GROUP_TO_SPACE', {
								name: space.name,
								gid: groups[gid].gid,
								backend: groups[gid].backend,
								isLocked: groups[gid].is_locked,
							})
						}
					})
			}
		},
	},
}
</script>

<style>

.modal-container {
	display: flex !important;
	min-height: 520px !important;
	max-height: 520px !important;
}

.header-select-groups {
	display: flex;
	flex-direction: row;
	align-items: center;
	width: 100%;
	justify-content: space-between;
}

.header-select-groups-title {
	width: 90%;
}

.title-select-groups {
	position: relative;
	left: 20px;
	font-weight: bold;
	font-size: 18px;
}

.content-select-groups {
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-items: center;
	margin: 10px;
	min-width: 580px;
	max-width: 580px;
}

.select-groups-list {
	flex-grow: 1;
	margin-top: 5px;
	border-style: solid;
	border-width: 1px;
	border-color: transparent;
	width: 82%;
	overflow: scroll;
}

.group-entry {
	justify-content: space-between;
	padding-left: 1px;
	font-size: 20px;
}

.group-entry,
.group-entry div {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.group-select-name {
	margin-left: 10px;
	max-width: 440px;
}

.select-group {
	cursor: pointer !important;
}

.select-groups-actions {
	display: flex;
	flex-flow: row-reverse;
	margin-top: 10px;
}
</style>
