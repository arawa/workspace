import Vue from 'vue'
import { translate, translatePlural } from 'nextcloud-l10n'

import SidebarTab from './SidebarTab'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

const View = Vue.extend(SidebarTab)
let tabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		const SidebarTab = new OCA.Files.Sidebar.Tab({
			id: 'workspace',
			name: t('workspace', 'Workspace'),
			icon: 'icon-rename',

			mount(el, fileInfo, context) {
				if (tabInstance) {
					tabInstance.$destroy()
				}
				tabInstance = new View({
					// Better integration with vue parent component
					parent: context,
				})
				// Only mount after we have all the info we need
				tabInstance.update(fileInfo)
				tabInstance.$mount(el)
			},
			update(fileInfo) {
				tabInstance.update(fileInfo)
			},
			destroy() {
				tabInstance.$destroy()
				tabInstance = null
			},
			enabled(fileInfo) {
				// return (fileInfo && !fileInfo.isDirectory());
				return true
			},
		})
		OCA.Files.Sidebar.registerTab(SidebarTab)
	}
})
