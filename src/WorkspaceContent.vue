<template>
  <NcAppContent>
			<NcAppContentDetails>
				<div
					v-if="$store.state.loading"
					class="lds-ring">
					<div /><div /><div /><div />
				</div>
				<div v-else class="workspace-content">
					<router-view />
				</div>
			</NcAppContentDetails>
		</NcAppContent>
</template>

<script>
export default {
	name: 'WorkspaceContent',
}
</script>
