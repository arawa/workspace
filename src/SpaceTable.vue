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
		<div v-if="loading" class="lds-ring">
			<div /><div /><div /><div />
		</div>
		<table v-else-if="Object.keys($store.state.spaces).length" class="table-spaces">
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
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'SpaceTable',
	components: {
		Avatar,
		EmptyContent,
	},
	data() {
		return {
			loading: false, // true when we are loading the data from the server
		}
	},
	created() {
		// eslint-disable-next-line
		console.log(this.$store.state)
		if (Object.entries(this.$store.state.spaces).length === 0) {
			this.loading = true
			axios.get(generateUrl('/apps/workspace/spaces'))
				.then(resp => {
					// Checks for application errors
					if (resp.status !== 200) {
						this.$notify({
							title: t('workspace', 'Error'),
							text: t('workspace', 'An error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + resp.statusText,
							type: 'error',
						})
						this.loading = false
						return
					}

					// Initialises the store
					Object.values(resp.data).forEach(space => {
						let codeColor = space.color_code
						if (space.color_code === null) {
							codeColor = '#' + (Math.floor(Math.random() * 2 ** 24)).toString(16).padStart(0, 6)
						}
						this.$store.commit('addSpace', {
							color: codeColor,
							groups: space.groups,
							id: space.id,
							groupfolderId: space.groupfolder_id,
							isOpen: false,
							name: space.space_name,
							quota: this.convertQuotaForFrontend(space.quota),
							users: space.users,
						})
					})

					// Finished loading
					this.loading = false
				})
				.catch((e) => {
					this.$notify({
						title: t('workspace', 'Network error'),
						text: t('workspace', 'A network error occured while trying to retrieve workspaces.') + '<br>' + t('workspace', 'The error is: ') + e,
						type: 'error',
					})
					this.loading = false
				})
		}
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
			return Object.values(space.users).filter((u) => u.role === 'admin')
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
/*
	Code for the loading.
	Source code: https://loading.io/css/
*/
.lds-ring {
	display: inline-block;
	position: relative;
	width: 80px;
	height: 80px;
}

.lds-ring div {
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 64px;
	height: 64px;
	margin: 8px;
	border: 8px solid var(--color-primary-element);
	border-radius: 50%;
	animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
	border-color: var(--color-primary-element) transparent transparent transparent;
}

.lds-ring div:nth-child(1) {
	animation-delay: -0.45s;
}

.lds-ring div:nth-child(2) {
	animation-delay: -0.3s;
}

.lds-ring div:nth-child(3) {
	animation-delay: -0.15s;
}

@keyframes lds-ring {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
}

.main-div {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: space-between;
}

</style>
