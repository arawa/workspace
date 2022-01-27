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
					<Actions default-icon="icon-add">
						<ActionButton
							icon="icon-add"
							:close-after-click="true"
							@click="toggleShowSelectUsersModal">
							{{ t('workspace', 'Add users') }}
						</ActionButton>
					</Actions>
				</div>
				<Actions>
					<ActionButton
						v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						v-show="!showRenameGroupInput"
						icon="icon-rename"
						@click="toggleShowRenameGroupInput">
						{{ t('workspace', 'Rename group') }}
					</ActionButton>
					<ActionInput
						v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						v-show="showRenameGroupInput"
						ref="renameGroupInput"
						icon="icon-group"
						@submit="onRenameGroup">
						{{ t('workspace', 'Group name') }}
					</ActionInput>
					<ActionButton
						v-if="!$store.getters.isGEorUGroup($route.params.space, $route.params.group)"
						icon="icon-delete"
						@click="deleteGroup">
						{{ t('workspace', 'Delete group') }}
					</ActionButton>
				</Actions>
			</div>
		</div>
		<UserTable :space-name="$route.params.group" />
		<Modal
			v-if="showSelectUsersModal"
			@close="toggleShowSelectUsersModal">
			<SelectUsers :space-name="$route.params.group" @close="toggleShowSelectUsersModal" />
		</Modal>
	</div>
</template>

<script>
import { ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from './constants'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import SelectUsers from './SelectUsers'
import UserTable from './UserTable'

export default {
	name: 'GroupDetails',
	components: {
		Actions,
		ActionButton,
		ActionInput,
		Modal,
		SelectUsers,
		UserTable,
	},
	data() {
		return {
			showRenameGroupInput: false, // true to display 'Rename Group' ActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
		}
	},
	methods: {
		deleteGroup() {
			// Prevents deleting GE- and U- groups
			const space = this.$store.state.spaces[this.$route.params.space]
			if (this.$route.params.group === ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX + space.id
			|| this.$route.params.group === ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.id) {
				// TODO Inform user
				return
			}

			this.$store.dispatch('deleteGroup', {
				name: this.$route.params.space,
				gid: this.$route.params.group,
			})
		},
		onRenameGroup(e) {
			// Hides ActionInput
			this.toggleShowRenameGroupInput()

			// Don't accept empty names
			const group = e.target[1].value
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
