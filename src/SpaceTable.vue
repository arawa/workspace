<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
  -->

<template>
	<div class="main-div">
		<div class="header" />
		<table v-if="Object.keys($store.state.spaces).length" class="table-spaces">
			<thead>
				<tr>
					<th />
					<th>{{ t('workspace', 'Workspace name') }}</th>
					<th>{{ t('workspace', 'Quota') }}</th>
					<th>{{ t('workspace', 'Space administrators') }}</th>
				</tr>
			</thead>
			<tr v-for="(space,name) in $store.state.spaces"
				:key="name"
				@click="openSpace(name)">
				<td style="width: 50px;">
					<span class="color-dot-home" :style="{background: space.color}" />
				</td>
				<td> {{ name }} </td>
				<td> {{ space.quota }} </td>
				<td>
					<div class="admin-avatars">
						<Avatar v-for="user in workspaceManagers(space)"
							:key="user.uid"
							:style="{ marginRight: 2 + 'px' }"
							:display-name="user.name"
							:user="user.uid" />
					</div>
				</td>
			</tr>
		</table>
		<EmptyContent v-else>
			<p>No spaces</p>
			<template #desc>
				You have not yet created any workspace
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'SpaceTable',
	components: {
		Avatar,
		EmptyContent,
	},
	methods: {
		convertQuotaForFrontend(quota) {
			if (quota === '-3') {
				return 'unlimited'
			} else {
				const units = ['', 'KB', 'MB', 'GB', 'TB']
				let i = 0
				while (quota >= 1024) {
					quota = quota / 1024
					i++
				}
				return quota + units[i]
			}
		},
		// Returns all workspace's managers
		workspaceManagers(space) {
			return Object.values(space.users).filter((u) => this.$store.getters.isGeneralManager(u, space.name))
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

.color-dot-home {
	height: 35px;
	width: 35px;
	border-radius: 50%;
	display: block;
}

.table-spaces {
	width: 100%;
	margin-top: -81px;
}

.main-div {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: space-between;
}

tr:hover {
	background-color: #f5f5f5;
	cursor: pointer;
}

</style>
