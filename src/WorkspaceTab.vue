<template>
	<div class="content-wsp-sidebar">
		<h1>Hello</h1>
		<p class="message">
			World
		</p>
	</div>
</template>

<script>

export default {
	name: 'WorkspaceTab',
	components: {
	},
	data() {
		return {
			fileInfo: {},
		}
	},
	created() {
		console.debug('WorkspaceTab created')
	},
	beforeMount() {
		console.debug('WorkspaceTab beforeMount')
	},
	mounted() {
		console.debug('WorkspaceTab mounted')
		console.debug('WorkspaceTab this.fileInfo', this.fileInfo)
	},
	methods: {
		/**
			* updates current resource
			*
			* @param {object} fileInfo file information
		 */
		async update(fileInfo) {
			this.fileInfo = fileInfo
		},
	},
}
</script>

<style>
.message {
	color: purple;
}

.content-wsp-sidebar {
	background-color: cyan;
	width: 100%;
}
</style>
