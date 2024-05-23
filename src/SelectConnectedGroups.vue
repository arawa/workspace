<template>
	<div class="container-select-groups">
		<header class="header-select-groups">
			<h1>{{ t('workspace', 'Add a group') }}</h1>
		</header>

		<div class="body-select-groups">
			<NcSelect
				class="searchbar-groups"
				track-by="gid"
				label="displayName"
				limit="10"
				:options="groupsSelectable"
				:placeholder="t('workspace', 'Start typing text to search for groups')"
				@search="lookupGroups"
				@close="groupsSelectable=[]" />
		</div>
		<div class="content-group-list">
			<div v-if="groupsSelected.length !== 0"
				class="select-group-list">
				<p>test</p>
			</div>
			<NcEmptyContent v-else
				class="content-group-list-empty"
				:title="t('workspace', 'No group selected')" />
		</div>
		<NcButton type="secondary"
			class="btn-add-groups">
			{{ t('workspace', 'Add') }}
		</NcButton>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'SelectConnectedGroups',
	components: {
		NcButton,
		NcEmptyContent,
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
			groupsSelected: [],
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

<style scoped>
.container-select-groups {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
}

.header-select-groups {
	display: flex;
	align-items: start;
	padding: 10px;
	font-weight: bold;
	align-self: start;
	margin-left: 16px;
}

.header-select-groups h1 {
	margin: 10px;
	font-size: 24px;
}

.body-select-groups {
	display: flex;
}

.searchbar-groups {
	width: 500px;
}

.content-group-list {
	width: 80%;
	height: 332px;
	padding: 8px;
}

.content-group-list-empty {
	width: 100%;
	height: 100%;
	margin: 0px 0px !important;
	justify-content: center;
}

.btn-add-groups {
	margin-top: 8px;
}
</style>
