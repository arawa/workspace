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
				<NcActions>
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
			<SelectUsers :space-name="$route.params.group" @close="toggleShowSelectUsersModal" />
		</NcModal>
	</div>
</template>

<script>
import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from './constants.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import SelectUsers from './SelectUsers.vue'
import UserGroup from './services/Groups/UserGroup.js'
import UserTable from './UserTable.vue'

export default {
	name: 'GroupDetails',
	components: {
		NcActions,
		NcActionButton,
		NcActionInput,
		NcModal,
		SelectUsers,
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
			if (this.$route.params.group === ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + space.id
			|| UserGroup.getUserGroup(space) + ESPACE_USERS_PREFIX + space.id) {
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

			// Don't accept empty names
			const group = e.target[0].value
			if (!group) {
				// TODO Inform user
				return
			}

			const space = this.$store.state.spaces[this.$route.params.space]

			// Prevents renaming SPACE-GE- and SPACE-U- groups
			if (group === ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + space.id
				|| group === ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.id) {
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
	margin-top: -40px;
}

</style>
