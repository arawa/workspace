<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div>
		<div class="header" />
		<table>
			<thead>
				<tr>
					<th />
					<th>{{ t('workspace', 'Workspace name') }}</th>
					<th>{{ t('workspace', 'Quota') }}</th>
					<th>{{ t('workspace', 'Space administrators') }}</th>
				</tr>
			</thead>
			<tr v-for="(space,name) in sortedSpaces"
				:key="name">
				<td style="width: 50px;">
					<span class="color-dot" :style="{background: space.color}" />
				</td>
				<td> {{ name }} </td>
				<td> {{ space.quota }} </td>
				<td>
					<Avatar v-for="user in adminUsers(space)"
						:key="user"
						:style="{ marginRight: 2 + 'px' }"
						:display-name="user"
						:user="user" />
				</td>
			</tr>
		</table>
	</div>
</template>

<script>
import Avatar from '@nextcloud/vue/dist/Components/Avatar'

export default {
	name: 'SpaceTable',
	components: {
		Avatar,
	},
	computed: {
		// Returns a sorted version of this.$root.$data.spaces
		sortedSpaces() {
			const sortedSpaces = {}
			Object.keys(this.$root.$data.spaces).sort().forEach((value, index) => {
				sortedSpaces[value] = this.$root.$data.spaces[value]
			})
			return sortedSpaces
		},
	},
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			return space.users.filter(user => user.role === 'admin').map(user => user.name)
		},
	},
}
</script>

<style>
.color-dot {
	height: 35px;
	width: 35px;
	border-radius: 50%;
	display: block;
}
</style>
