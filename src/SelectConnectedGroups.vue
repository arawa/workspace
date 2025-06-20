<template>
	<NcModal class="modal-connected-groups"
		@close="close()">
		<div class="container-select-groups">
			<header class="header-select-groups">
				<h1>
					{{ t('workspace', 'Add a group') }}
				</h1>
			</header>
			<div class="body-select-groups">
				<NcSelect
					class="searchbar-groups"
					track-by="gid"
					label="displayName"
					:limit="10"
					:options="groupsSelectable"
					:placeholder="t('workspace', 'Start typing text to search for groups')"
					:loading="loadingGroups"
					:append-to-body="false"
					:user-select="true"
					@option:selected="addGroupToBatch"
					@search="debounceLookGroups"
					@close="groupsSelectable=[]">
					<template #no-options>
						<span />
					</template>
				</NcSelect>
			</div>
			<div class="content-group-list">
				<div v-if="groupsSelected.length !== 0"
					class="select-group-list">
					<div v-for="group in groupsSelected"
						:key="group.gid"
						class="group-item">
						<div class="group-avatar">
							<NcAvatar
								:display-name="group.displayName"
								:show-user-status="false"
								:is-no-user="true" />
							<div class="groupname">
								<span>{{ group.displayName }}</span>
							</div>
						</div>
						<div>
							<NcActions>
								<NcActionButton icon="icon-delete"
									@click="removeGroupFromBatch(group)">
									{{ t('workspace', 'remove group from selection') }}
								</NcActionButton>
							</NcActions>
						</div>
					</div>
				</div>
				<NcEmptyContent v-else
					class="content-group-list-empty"
					:name="t('workspace', 'No group selected')" />
			</div>
			<NcButton type="secondary"
				class="btn-add-groups"
				@click="validate">
				{{ t('workspace', 'Add') }}
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import { generateUrl } from '@nextcloud/router'
import debounce from 'debounce'

export default {
	name: 'SelectConnectedGroups',
	components: {
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcEmptyContent,
		NcModal,
		NcSelect,
	},
	props: {
		space: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loadingGroups: false,
			groupsSelectable: [],
			groupsSelected: [],
		}
	},
	methods: {
		addGroupToBatch(group) {
			this.groupsSelected.push(group)
		},
		removeGroupFromBatch(group) {
			this.groupsSelected = this.groupsSelected.filter((g) => {
				return g.displayName !== group.displayName
			})
		},
		debounceLookGroups: debounce(function(term) {
			this.lookupGroups(term)
		}, 500),
		lookupGroups(term) {
			if (term === undefined || term === '') {
				return
			}

			this.loadingGroups = true
			const space = this.space
			const groupsPresents = Object.keys(space.added_groups) || []

			axios.get(generateUrl('/apps/workspace/groups'), {
				params: {
					pattern: term,
					ignoreSpaces: true,
					groupsPresents,
				},
			})
				.then(response => {
					let groups = []

					for (const key in response.data) {
						groups.push(response.data[key])
					}

					const groupnames = this.groupsSelected.map((group) => group.displayName)
					groups = groups.filter((group) => !groupnames.includes(group.displayName))

					this.groupsSelectable = groups
				})
				.catch(reason => {
					console.error(reason.message)
				})
				.finally(() => {
					this.loadingGroups = false
				})
		},
		validate() {
			this.$emit('close')
			// todo: Call api
			this.groupsSelected.forEach(group => {
				this.$store.dispatch('addConnectedGroupToWorkspace', {
					space: this.space,
					group,
				})
			})
		},
		close() {
			this.$emit('close')
		},
	},
}
</script>

<style scoped>

.modal-connected-groups :deep(.modal-wrapper .modal-container) {
	min-height: auto;
}

.container-select-groups {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
}

.header-select-groups {
	display: flex;
	padding: 10px;
	font-weight: bold;
	align-self: start;
	margin-left: 16px;
}

.header-select-groups h1 {
	margin: 10px;
	margin-bottom: 16px;
	font-size: 20px;
}

.body-select-groups {
	display: flex;
}

.searchbar-groups {
	width: 500px;
}

.body-select-groups :deep(.v-select.select.vs--open .vs__dropdown-toggle) {
	border-width: 2px;
	border-color: var(--color-border-dark);
	border-bottom: rgb(0,0,0,0);
}

.body-select-groups:hover :deep(.v-select.select .vs__dropdown-toggle) {
	border-color: var(--color-primary);
}

.body-select-groups:hover :deep(.v-select.select.vs--open) .vs__dropdown-menu {
	border-color: var(--color-primary) !important;
}

.body-select-groups :deep(.v-select.select.vs--open .vs__dropdown-menu) {
	border-width: 2px !important;
	border-color: var(--color-border-dark) !important;
}

.body-select-groups :deep(.v-select.select .vs__dropdown-toggle) {
	border-width: 2px;
	border-color: var(--color-border-dark);
}

.content-group-list {
	width: 80%;
	height: 400px;
	padding: 8px;
	margin-top: 16px;
}

.content-group-list-empty {
	width: 100%;
	height: 100%;
	margin: 0px 0px !important;
	justify-content: center;
}

.content-group-list-empty h2 {
	font-size: 26px;
}

.btn-add-groups {
	margin: 24px 0 24px 0;
}

.groupname {
	margin-left: 14px;
}

.select-group-list {
	display: flex;
	flex-direction: column;
	overflow: scroll;
	height: 100%;
}

.group-item {
	display: flex;
	justify-content: space-between;

}

.group-avatar {
	display: flex;
	align-items: center;
}

</style>
