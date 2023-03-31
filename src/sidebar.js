import Vue from 'vue'
import WorkspaceTab from './WorkspaceTab.vue'
import { translate, translatePlural } from '@nextcloud/l10n'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

// Init Workspace Tab Service
if (!window.OCA.Workspace) {
	window.OCA.Workspace = {}
}

const View = Vue.extend(WorkspaceTab)
let TabInstance = null

const projectTab = new OCA.Files.Sidebar.Tab({
	id: 'workspace',
	name: t('workspace', 'Workspace'),
	icon: 'icon-groups',

	async mount(el, fileInfo, context) {
		if (TabInstance) {
			TabInstance.$destroy()
		}
		TabInstance = new View({
			// Better integration with vue parent component
			parent: context,
		})
		// Only mount after we have all the info we need
		TabInstance.update(fileInfo)
		await TabInstance.$mount(el)
	},
	update(fileInfo) {
		TabInstance.update(fileInfo)
	},
	destroy() {
		TabInstance.$destroy()
		TabInstance = null
	},
})

window.addEventListener('DOMContentLoaded', function() {
	console.debug('projectTab', projectTab)
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(projectTab)
	}
})
