<!--
  @copyright Copyright (c) 2017 Arawa

  @license GNU AGPL version 3 or any later version

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<NcContent app-name="workspace">
		<NcAppContent>
			<NcEmptyContent :name="t('workspace', 'Team folders is not enabled')">
				<template #description>
					{{ descriptionParts.textBeforeLink }}<a v-if="descriptionParts.linkText"
						href="https://apps.nextcloud.com/apps/groupfolders"
						class="external"
						target="_blank"
						rel="noopener noreferrer">{{ descriptionParts.linkText }}</a>{{ descriptionParts.textAfterLink }}
				</template>
				<template #icon>
					<NcIconSvgWrapper :svg="GroupfoldersOff" />
				</template>
				<template #action>
					<div class="groupfolders-disabled__actions">
						<NcButton v-if="isInstanceAdmin" :href="linkAppStore" variant="primary">
							{{ t('workspace', 'Open Team folders in the app store') }}
							<template #icon>
								<StorefrontOutline :size="20" />
							</template>
						</NcButton>
						<NcButton :href="linkInstance" :variant="isInstanceAdmin ? 'secondary' : 'primary'">
							{{ t('workspace', 'Return to home') }}
							<template #icon>
								<Home :size="20" />
							</template>
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
		</NcAppContent>
	</NcContent>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import Home from 'vue-material-design-icons/Home.vue'
import StorefrontOutline from 'vue-material-design-icons/StorefrontOutline.vue'
import GroupfoldersOff from '../img/groupfolders_off.svg?raw'

export default {
	name: 'GroupfoldersDisabled',
	components: {
		NcAppContent,
		NcButton,
		NcContent,
		NcEmptyContent,
		NcIconSvgWrapper,
		Home,
		StorefrontOutline,
	},
	data() {
		return {
			isInstanceAdmin: getCurrentUser()?.isAdmin ?? false,
			linkInstance: generateUrl('/'),
			linkAppStore: generateUrl('/settings/apps/files/groupfolders'),
			GroupfoldersOff,
		}
	},
	computed: {
		descriptionParts() {
			// Keep the placeholders in the translated text and split around them,
			// so the link is rendered by the template instead of v-html
			const text = t(
				'workspace',
				'Workspace requires the {linkStart}Team folders{linkEnd} app. Please contact your Nextcloud administrator to install and enable it.',
				undefined,
				{ sanitize: false },
			)
			const [textBeforeLink, linkText = '', textAfterLink = ''] = text.split(/{linkStart}|{linkEnd}/)
			return { textBeforeLink, linkText, textAfterLink }
		},
	},
}
</script>

<style scoped>
.groupfolders-disabled__actions {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
