<template>
	<div>
		<h1>Connect a group</h1>

		<NcSelect
			label="Start typing to lookup groups"
			limit="10"
			:options="groupsSelectable"
			@search="lookupGroups"
			@close="groupsSelectable=[]" />
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
				},
			})
				.then(response => {
					console.debug(response.data)
					// response.data is an object
					this.groupsSelectable = response.data
				})
				.catch(reason => {
					console.error(reason.message)
				})
		},
	},
}
</script>

<style>
</style>
