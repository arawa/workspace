import { generateUrl } from '@nextcloud/router'
import showNotificationError from '../services/Notifications/NotificationError.js'
import axios from '@nextcloud/axios'
import { LIMIT_WORKSPACES_PER_PAGE } from '../constants.js'

export const WorkspacesLoader = {
	data() {
		return {
			nextPage: true,
		}
	},
	computed: {
		messageLoader() {
			return t('workspace', '{number} workspaces loading...', { number: LIMIT_WORKSPACES_PER_PAGE })
		},
	},
	methods: {
		next(visible) {
			const pageMax = this.countMaxPages()

			if (visible) {
				if (this.$store.getters.workspaceCurrentPage <= pageMax) {
					axios.get(generateUrl('/apps/workspace/spaces'), {
						params: {
							page: this.$store.getters.workspaceCurrentPage,
						},
					})
						.then(resp => {
							if (resp.status !== 200) {
								const text = t('workspace', 'An error occurred while trying to retrieve workspaces.<br>Error: {error}', { error: resp.statusText })
								showNotificationError('Error', text, 4000)
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
							showNotificationError('Network error', text, 5000)
						})
				}
			}

			this.showNextPage()
		},
		countMaxPages() {
			return Math.ceil(this.$store.getters.countTotalWorkspaces / LIMIT_WORKSPACES_PER_PAGE)
		},
		showNextPage() {
			const pageMax = this.countMaxPages()
			if (this.$store.getters.workspaceCurrentPage >= pageMax) {
				this.nextPage = false
			}
		},
	},
}
