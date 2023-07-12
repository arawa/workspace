<!--
	@copyright Copyright (c) 2017 Arawa

	@author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>

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
	<NcAppNavigationItem
		:key="space.id"
		:class="$route.params.space === spaceName ? 'space-selected' : ''"
		:allow-collapse="true"
		:open="$route.params.space === spaceName"
		:title="spaceName"
		:to="{path: `/workspace/${spaceName}`}">
		<NcAppNavigationIconBullet slot="icon" :color="space.color" />
		<NcCounterBubble slot="counter" class="user-counter">
			{{ $store.getters.spaceUserCount(spaceName) }}
		</NcCounterBubble>
		<div>
			<NcAppNavigationItem
				v-for="group in sortedGroups(Object.values(space.groups), spaceName)"
				:key="group.gid"
				icon="icon-group"
				:to="{path: `/group/${spaceName}/${group.gid}`}"
				:title="group.displayName">
				<NcCounterBubble slot="counter" class="user-counter">
					{{ $store.getters.groupUserCount( spaceName, group.gid) }}
				</NcCounterBubble>
			</NcAppNavigationItem>
		</div>
	</NcAppNavigationItem>
</template>

<script>
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationIconBullet from '@nextcloud/vue/dist/Components/NcAppNavigationIconBullet.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import { getLocale } from '@nextcloud/l10n'

export default {
	name: 'SpaceMenuItem',
	components: {
		NcAppNavigationItem,
		NcAppNavigationIconBullet,
		NcCounterBubble,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
		spaceName: {
			type: String,
			required: true,
		},
	},
	methods: {
		// sorts groups alphabetically
		sortedGroups(groups, space) {
			groups.sort((a, b) => {
				// Makes sure the GE- group is first in the list
				// These tests must happen before the tests for the U- group
				const GEGroup = this.$store.getters.GEGroup(space)
				if (a.gid === GEGroup) {
					return -1
				}
				if (b.gid === GEGroup) {
					return 1
				}
				// Makes sure the U- group is second in the list
				// These tests must be done after the tests for the GE- group
				const UGroup = this.$store.getters.UGroup(space)
				if (a.gid === UGroup) {
					return -1
				}
				if (b.gid === UGroup) {
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

<style>
.user-counter {
	margin-right: 5px;
}
</style>
