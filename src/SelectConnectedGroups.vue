<template>
	<div class="container-select-groups">
		<header class="header-select-groups">
			<h1>Connect a group</h1>
		</header>

		<div class="body-select-groups">
			<NcSelect
				class="searchbar-groups"
				track-by="gid"
				label="displayName"
				limit="10"
				:options="groupsSelectable"
				@search="lookupGroups"
				@close="groupsSelectable=[]" />
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'SelectConnectedGroups',
	components: {
		NcSelect,
	},
	data() {
		return {
			groupsSelectable: [
				{
					title: 'Groups select',
					props: {
						inputId: 1,
						multiple: false,
						closeOnSelect: true,
						options: [],
					},
				},
			],
		}
	},
	methods: {
		lookupGroups(term) {
			if (term === undefined || term === '') {
				return
			}

			axios.get(generateUrl('/apps/workspace/groups'), {
				params: {
					pattern: term,
					ignoreSpaces: true,
				},
			})
				.then(response => {
					const groups = []

					for (const key in response.data) {
						groups.push(response.data[key])
					}

					this.groupsSelectable = groups
				})
				.catch(reason => {
					console.error(reason.message)
				})
		},
	},
}
</script>

<style>
.container-select-groups {
	display: flex;
	flex-direction: column;
	width: 100%;
	justify-content: center;
	align-items: center;
}

.header-select-groups {
	display: flex;
	align-items: start;
	width: 100%;
	padding: 10px;
}

.header-select-groups h1 {
	margin: 10px;
	font-size: 24px;
}

.body-select-groups {
	display: flex;
}

.searchbar-groups {
	width: 400px;
}

</style>
