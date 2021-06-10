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
			<tr v-for="(space,name) in $store.getters.sortedSpaces"
				:key="name"
				@click="openSpace(name)">
				<td style="width: 50px;">
					<span class="color-dot" :style="{background: space.color}" />
				</td>
				<td> {{ name }} </td>
				<td> {{ space.quota }} </td>
				<td>
					<div class="admin-avatars">
						<Avatar v-for="user in adminUsers(space)"
							:key="user.name"
							:style="{ marginRight: 2 + 'px' }"
							:display-name="user.name"
							:user="user.name" />
					</div>
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
	methods: {
		// Returns the list of administrators of a space
		adminUsers(space) {
			return Object.values(space.users).filter((u) => u.role === 'admin').map((u) => u.name)
		},
		openSpace(name) {
			this.$store.state.spaces[name].isOpen = true
			this.$router.push({
				path: `/workspace/${name}`,
			})
		},
	},
}
</script>

<style>
.admin-avatars {
	display: flex;
	flex-flow: row-reverse;
}
</style>
