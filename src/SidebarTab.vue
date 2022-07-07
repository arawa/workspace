<template>
	<div>
		<p>
			coucou
		</p>
	</div>
</template>

<script>

export default {
	name: 'SidebarTab',
	components: {
	},
	data() {
		return {
			loading: false,
			token: null,
			fileInfo: {},
			shares: [],
		}
	},
}
</script>

<style scoped>
#tab-sharerenamer {
	height: 100%;
	padding: 0;
}
</style>
