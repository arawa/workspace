import Vue from 'vue'
import { translate, translatePlural } from 'nextcloud-l10n'

import SidebarTab from './SidebarTab'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

const View = Vue.extend(SidebarTab)
let tabInstance = null
console.error('Ceci est un test depuis main.js')
const workspaceTab = new OCA.Files.Sidebar.Tab({
	id: 'workspace',
	name: t('workspace', 'Workspace'),
	icon: 'icon-rename',

	async mount(el, fileInfo, context) {
		if (tabInstance) {
			tabInstance.$destroy()
		}
		tabInstance = new View({
			// Better integration with vue parent component
			parent: context,
		})
		// Only mount after we have all the info we need
		await tabInstance.update(fileInfo)
		tabInstance.$mount(el)
	},
	update(fileInfo) {
		tabInstance.update(fileInfo)
	},
	destroy() {
		tabInstance.$destroy()
		tabInstance = null
	},
})

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(workspaceTab)
	} else {
		console.error('problème dans le fonctionnement des sidebars Nextcloud')
	}
})
