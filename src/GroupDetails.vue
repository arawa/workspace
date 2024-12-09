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
	<div v-if="!$store.state.loadingUsersWaitting">
		<div class="header">
			<div class="group-name">
				<div :class="isAddedGroup ? 'icon-added-group' : 'icon-group'" />
				<span class="titles-for-space">
					{{ $store.getters.groupName($route.params.space, decodeURIComponent(decodeURIComponent($route.params.slug))) }}
				</span>
			</div>
			<div class="group-actions">
				<div v-if="!isAddedGroup">
					<NcActions default-icon="icon-add">
						<NcActionButton icon="icon-add"
							:close-after-click="true"
							@click="toggleShowSelectUsersModal">
							{{ t('workspace', 'Add users') }}
						</NcActionButton>
					</NcActions>
				</div>
				<NcActions ref="ncAction">
					<NcActionButton v-if="!$store.getters.isGEorUGroup($route.params.space, decodeURIComponent(decodeURIComponent($route.params.slug))) && !isAddedGroup"
						v-show="!showRenameGroupInput"
						icon="icon-rename"
						@click="toggleShowRenameGroupInput">
						{{ t('workspace', 'Rename group') }}
					</NcActionButton>
					<NcActionInput v-if="!$store.getters.isGEorUGroup($route.params.space, decodeURIComponent(decodeURIComponent($route.params.slug)))"
						v-show="showRenameGroupInput"
						ref="renameGroupInput"
						icon="icon-group"
						@submit="onRenameGroup">
						{{ t('workspace', 'Group name') }}
					</NcActionInput>
					<NcActionButton v-if="!$store.getters.isGEorUGroup($route.params.space, decodeURIComponent(decodeURIComponent($route.params.slug))) && !isAddedGroup"
						icon="icon-delete"
						@click="toggleRemoveGroupModal">
						{{ t('workspace', 'Delete group') }}
					</NcActionButton>
					<NcActionButton v-if="isAddedGroup"
						icon="icon-delete"
						@click="toggleRemoveConnectedGroupModal">
						{{ t('workspace', 'Remove connected group') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>
		<UserTable :space-name="decodeURIComponent(decodeURIComponent($route.params.slug))" :editable="!isAddedGroup" />
		<SelectUsers v-if="showSelectUsersModal" :space-name="decodeURIComponent(decodeURIComponent($route.params.slug))" @close="toggleShowSelectUsersModal" />
		<AlertRemoveGroup v-if="showRemoveConnectedGroupModal"
			:message="t('workspace', 'Warning, after removal of group <b>{groupname}</b>, its users will lose access to the <b>nouveaux espaces</b> workspace, with the exception of:<br><br>- Workspace Managers (<b>WM-nouveaux espaces</b>)<br>- users who are members of <b>Groupe Workspace</b> (prefixed <b>G-</b>)<br>- users who are members of another Added Group<br>- users manually added from the Workspace <b>nouveaux espaces</b>', { groupname: decodeURIComponent(decodeURIComponent($route.params.slug)) }, null, { escape: false })"
			@cancel="closeConnectedGroupModal"
			@remove-group="removeConnectedGroup" />
		<AlertRemoveGroup v-if="showRemoveGroupModal"
			:message="t('workspace', 'Attention, après la suppression du groupe {groupname}, ses utilisateurs conserveront l\'accès à l\'espace de travail {spacename}', { groupname: decodeURIComponent(decodeURIComponent($route.params.slug)), spacename: this.$route.params.space })"
			@cancel="closeRemoveGroupModal"
			@remove-group="deleteGroup" />
	</div>
</template>

<script>
import { PREFIX_MANAGER, PREFIX_USER } from './constants.js'
import AlertRemoveGroup from './AlertRemoveGroup.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import SelectUsers from './SelectUsers.vue'
import UserGroup from './services/Groups/UserGroup.js'
import UserTable from './UserTable.vue'
import ManagerGroup from './services/Groups/ManagerGroup.js'

export default {
	name: 'GroupDetails',
	components: {
		AlertRemoveGroup,
		NcActions,
		NcActionButton,
		NcActionInput,
		SelectUsers,
		UserTable,
	},
	data() {
		return {
			showRenameGroupInput: false, // true to display 'Rename Group' NcActionInput
			showSelectUsersModal: false, // true to display user selection Modal windows
			showRemoveConnectedGroupModal: false,
			showRemoveGroupModal: false,
		}
	},
	computed: {
		// The title to display at the top of the page
		isAddedGroup() {
			return this.$store.getters.isSpaceAddedGroup(this.$route.params.space, decodeURIComponent(this.$route.params.slug))
		},
    connectedGroupMessage() {
      const text = t('workspace', 'Warning, after removal of group :{groupname}', { groupname: '<b>aaaa</b>'})
      console.debug('text', text)
      return 'Warning, after removal of group :<b>aaaa</b>'
    },
	},
	mounted() {
		const space = this.$store.state.spaces[this.$route.params.space]
		this.$store.dispatch('loadUsers', { space })
	},
	methods: {
		deleteGroup() {
			// Prevents deleting GE- and U- groups
			const space = this.$store.state.spaces[this.$route.params.space]
			if (decodeURIComponent(this.$route.params.slug) === PREFIX_MANAGER + space.id
			|| decodeURIComponent(this.$route.params.slug) === UserGroup.getGid(space)) {
				// TODO Inform user
				return
			}
			this.$store.dispatch('deleteGroup', {
				name: this.$route.params.space,
				gid: decodeURIComponent(this.$route.params.slug),
			})
		},
		toggleRemoveConnectedGroupModal() {
			this.showRemoveConnectedGroupModal = !this.showRemoveConnectedGroupModal
		},
		toggleRemoveGroupModal() {
			this.showRemoveGroupModal = !this.showRemoveGroupModal
		},
		closeConnectedGroupModal() {
			this.showRemoveConnectedGroupModal = false
		},
		closeRemoveGroupModal() {
			this.showRemoveGroupModal = false
		},
		removeConnectedGroup() {
			const space = this.$store.state.spaces[this.$route.params.space]
			const gid = decodeURIComponent(decodeURIComponent(this.$route.params.slug))

			const usersAreNotConnected = Object.values(space.users).filter((user) => user.is_connected === false && user.groups.includes(gid))
			const usersCount = space.added_groups[gid].usersCount - usersAreNotConnected.length

			this.$store.dispatch('substractionSpaceUserCount', {
				spaceName: space.name,
				usersCount
			})

			this.$store.dispatch('substractionGroupUserCount', {
				spaceName: space.name,
				gid: UserGroup.getGid(space),
				usersCount
			})

			this.$store.dispatch('removeConnectedGroup', {
				spaceId: space.id,
				gid,
				name: space.name
			})

			Object.keys(space.users).forEach(key => {

				if (space.users[key].groups.includes(gid)) {
					if (space.users[key].is_connected) {
						this.$store.commit('removeUserFromWorkspace', { name: space.name, user: space.users[key] })
					} else {
						this.$store.commit('removeUserFromGroup', { name: space.name, gid, user: space.users[key] })
					} 
				}
			})

			this.toggleRemoveConnectedGroupModal()
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
			const groupSpace = space.groups[decodeURIComponent(this.$route.params.slug)]

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
				gid: decodeURIComponent(this.$route.params.slug),
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
