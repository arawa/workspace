<template>
	<div v-if="isGroupfolder"
		class="content-wsp-sidebar">
		<h1>Hello</h1>
		<p class="message">
			World
		</p>
	</div>
	<NcEmptyContent v-else
		title="No Workspace or Groupfolder">
		<p>Sorry, this file is not a Workspace or Groupfolder.</p>
	</NcEmptyContent>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

export default {
	name: 'WorkspaceTab',
	components: {
		NcEmptyContent,
	},
	data() {
		return {
			fileInfo: {},
			isGroupfolder: false,
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
			// Ici, je peux faire un appel API pour savoir si le fichier est
			// un groupfolder ou non.
			if (this.fileInfo.mountType !== 'group') {
				this.isGroupfolder = false
				return
			}
			console.debug('WorkspaceTab - call the toggleIsGroupfolder function')
			this.toggleIsGroupfolder()
			console.debug('WorkspaceTab - this.fileInfo', {
				name: this.fileInfo.name,
				mountType: this.fileInfo.mountType,
				id: this.fileInfo.id,
			})
		},
		toggleIsGroupfolder() {
			this.isGroupfolder = !this.isGroupfolder
			console.debug('WorkspaceTab - toggleIsGroupfolder', this.isGroupfolder)
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
