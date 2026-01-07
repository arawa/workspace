import { generateUrl } from '@nextcloud/router'
import showNotificationError from '../services/Notifications/NotificationError.js'
import axios from '@nextcloud/axios'
import { LIMIT_WORKSPACES_PER_PAGE } from '../constants.js'

export const WorkspacesLoader = {
	computed: {
		messageLoader() {
			return t('workspace', '{number} workspaces...', { number: this.$store.getters.countWorkspaces })
		},
		nextPage() {
			return this.$store.getters.nextPage
		},
	},
	methods: {
		next(visible) {
			const pageMax = this.countMaxPages()

			if (visible) {
				if (this.$store.getters.workspaceCurrentPage <= pageMax) {
					axios.get(generateUrl('/apps/workspace/spaces'), {
						params: {
							offset: this.$store.getters.workspaceCurrentPage,
							search: this.$store.getters.searchWorkspace,
							limit: LIMIT_WORKSPACES_PER_PAGE,
						},
					})
						.then(resp => {
							if (resp.status !== 200) {
								const text = t('workspace', 'An error occurred while trying to retrieve workspaces.<br>Error: {error}', { error: resp.statusText })
								showNotificationError(t('workspace', 'Error'), text, 4000)
								return
							}

							const spaces = resp.data
							this.$store.commit('addSpaces', { spaces })
							this.$store.dispatch('recountWorkspaces')
							this.$store.dispatch('incrementWorkspacePage')

						})
						.catch((e) => {
							console.error('Problem to load spaces only', e)
							const text = t('workspace', 'A network error occurred while trying to retrieve workspaces.<br>Error: {error}', { error: e })
							showNotificationError(t('workspace', 'Network error'), text, 5000)
						})
				}
			}

			this.showNextPage()
		},
		countMaxPages() {
			return Math.ceil(this.$store.getters.countTotalWorkspacesByQuery / LIMIT_WORKSPACES_PER_PAGE)
		},
		toggleNextPage() {
			this.$store.dispatch('toggleNextPage')
		},
		showNextPage() {
			const pageMax = this.countMaxPages()

			if (this.$store.getters.workspaceCurrentPage >= pageMax) {
				this.toggleNextPage()
			}
		},
	},
}
