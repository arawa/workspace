<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2026 Baptiste Fotia <baptiste.fotia@arawa.fr>

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
		<p class="selected-users-chips__header">
			{{ t('workspace', 'Selected users') }}
			<NcCounterBubble :count="users.length" />
		</p>
		<p v-if="users.length === 0" class="selected-users-chips--empty">
			{{ t('workspace', 'No users selected') }}
		</p>
		<div v-else class="selected-users-chips">
			<NcChip v-for="user in users"
				:key="user.uid"
				:variant="chipVariant(user)"
				:aria-label-close="t('workspace', 'remove users from selection')"
				@close="$emit('remove-user', user)">
				<template #icon>
					<NcAvatar :user="user.uid"
						:display-name="user.name"
						:size="24"
						:show-user-status="false"
						disable-menu />
				</template>
				{{ user.name }}
				<button v-if="canChooseRole"
					type="button"
					class="selected-users-chips__role"
					:aria-pressed="isWorkspaceManager(user)"
					:disabled="isMemberManager(user)"
					:title="t('workspace', 'Set user as Workspace Manager')"
					@click="$emit('toggle-role', user)">
					<!-- TRANSLATORS WM = Is an abbreviation for "Workspace Manager" -->
					{{ t('workspace', 'WM') }}
				</button>
			</NcChip>
		</div>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcChip from '@nextcloud/vue/components/NcChip'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'

export default {
	name: 'SelectedUsersChips',
	components: {
		NcAvatar,
		NcChip,
		NcCounterBubble,
	},
	props: {
		users: {
			type: Array,
			required: true,
		},
	},
	emits: ['remove-user', 'toggle-role'],
	computed: {
		// The manager and user groups of a workspace give no choice of role
		canChooseRole() {
			return !this.$store.getters.isGEorUGroup(this.$route.params.space, decodeURIComponent(decodeURIComponent(this.$route.params.slug)))
		},
	},
	methods: {
		isWorkspaceManager(user) {
			return user.role === 'wm'
		},
		// A Workspace Manager already in the workspace stays one
		isMemberManager(user) {
			return this.$store.getters.isMember(this.$route.params.space, user)
				&& this.isWorkspaceManager(user)
		},
		// Red for a user who joins the workspace by joining one of its groups, primary for a Workspace Manager
		chipVariant(user) {
			if (this.$route.params.slug && !this.$store.getters.isMember(this.$route.params.space, user)) {
				return 'error'
			}
			return this.isWorkspaceManager(user) ? 'primary' : 'secondary'
		},
	},
}
</script>

<style scoped>
.selected-users-chips {
	display: flex;
	flex-wrap: wrap;
	align-content: flex-start;
	gap: 4px;
}

/* Stays in view while the chips scroll under it */
.selected-users-chips__header {
	position: sticky;
	top: 0;
	z-index: 1;
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 4px;
	background-color: var(--color-main-background);
	font-weight: bold;
}

.selected-users-chips--empty {
	color: var(--color-text-maxcontrast);
}

.selected-users-chips__role {
	min-height: 0;
	height: 20px;
	margin: 0;
	margin-inline-start: 6px;
	padding: 0 6px;
	border: 1px dashed currentColor;
	border-radius: var(--border-radius-small);
	background: transparent;
	color: inherit;
	font-size: 11px;
	font-weight: bold;
	opacity: .6;
	cursor: pointer;
}

.selected-users-chips__role[aria-pressed="true"] {
	border-style: solid;
	opacity: 1;
}

.selected-users-chips__role:disabled {
	cursor: default;
}
</style>
