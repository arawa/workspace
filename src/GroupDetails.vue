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
	<div>
		<div class="header">
			<div class="group-name">
				<div class="icon-group" />
				<span class="titles-for-space">
					{{ $store.getters.groupName($route.params.space, $route.params.group) }}
				</span>
			</div>
			<div class="group-actions">
				<div>
					<NcActions default-icon="icon-add">
						<NcActionButton icon="icon-add"
							:close-after-click="true"
							@click="toggleShowSelectUsersModal">
							{{ t('workspace', 'Add users') }}
						</NcActionButton>
					</NcActions>
				</div>
				<NcActions ref="ncAction">
					<NcActionButton v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						v-show="!showRenameGroupInput"
						icon="icon-rename"
						@click="toggleShowRenameGroupInput">
						{{ t('workspace', 'Rename group') }}
					</NcActionButton>
					<NcActionInput v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						v-show="showRenameGroupInput"
						ref="renameGroupInput"
						icon="icon-group"
						@submit="onRenameGroup">
						{{ t('workspace', 'Group name') }}
					</NcActionInput>
					<NcActionButton v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						icon="icon-delete"
						@click="deleteGroup">
						{{ t('workspace', 'Delete group') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>
		<UserTable :space-name="$route.params.group" />
		<NcModal v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<AddUsersTabs @close-sidebar="toggleShowSelectUsersModal" />
			<!-- <SelectUsers :space-name="$route.params.group" @close="toggleShowSelectUsersModal" /> -->
		</NcModal>
	</div>
</template>

<script>
import { PREFIX_MANAGER, PREFIX_USER } from './constants.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import UserGroup from './services/Groups/UserGroup.js'
import UserTable from './UserTable.vue'
import AddUsersTabs from './AddUsersTabs.vue'

export default {
	name: 'GroupDetails',
	components: {
		AddUsersTabs,
		NcActions,
		NcActionButton,
		NcActionInput,
		NcModal,
		UserTable,
	},
	data() {
		return {
			showRenameGroupInput: false, // true to display 'Rename Group' NcActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	methods: {
		deleteGroup() {
			// Prevents deleting GE- and U- groups
			const space = this.$store.state.spaces[this.$route.params.space]
			if (this.$route.params.group === PREFIX_MANAGER + space.id
			|| this.$route.params.group === UserGroup.getGid(space)) {
				// TODO Inform user
				return
			}
			this.$store.dispatch('deleteGroup', {
				name: this.$route.params.space,
				gid: this.$route.params.group,
			})
		},
		onRenameGroup(e) {
			// Hides NcActionInput
			this.toggleShowRenameGroupInput()
			this.$refs.ncAction.opened = false

			// Don't accept empty names
			let group = e.target[0].value
			if (!group) {
				// TODO Inform user
				return
			}

			const space = this.$store.state.spaces[this.$route.params.space]
			const groupSpace = space.groups[this.$route.params.group]

			group = ''.concat('G-', group, '-', space.name)
			group = groupSpace.displayName.replace(groupSpace.displayName, group)

			// Prevents renaming SPACE-GE- and SPACE-U- groups
			if (group === PREFIX_MANAGER + space.id
				|| group === PREFIX_USER + space.id) {
				// TODO Inform user
				return
			}

			// TODO Check already existing groups

			// Renames group
			this.$store.dispatch('renameGroup', {
				name: this.$route.params.space,
				gid: this.$route.params.group,
				newGroupName: group,
			})
		},
		toggleShowRenameGroupInput() {
			this.showRenameGroupInput = !this.showRenameGroupInput
			if (this.showRenameGroupInput === true) {
				this.$refs.renameGroupInput.$el.focus()
			}
		},
		toggleShowSelectUsersModal() {
			this.showSelectUsersModal = !this.showSelectUsersModal
		},
	},
}
</script>

<style>
.icon-group {
	min-width: 42px;
	min-height: 42px;
}

.group-actions,
.group-name,
.user-actions {
	display: flex;
}

.user-actions {
	flex-flow: row-reverse;
}

.group-name {
	margin-left: 8px;
	margin-top: -28px;
}

</style>
