<!--
  @copyright Copyright (c) 2017 Arawa

  @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
  @author 2021 Cyrille Bollu <cyrille@bollu.be>

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
	<NcEmptyContent
		:name="t('workspace', 'No workspace found')"
		:description="t('workspace', 'You aren\'t in any workspaces. Please contact a General Manager.')">
		<template #icon>
			<NcIconSvgWrapper :path="mdiFolderAlert" />
		</template>
		<template #action>
			<NcButton :href="linkInstance" variant="primary">
				{{ t('workspace', 'Return to home') }}
				<template #icon>
					<Home :size="20" />
				</template>
			</NcButton>
		</template>
	</NcEmptyContent>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import Home from 'vue-material-design-icons/Home.vue'
import { mdiFolderAlert } from '@mdi/js'

export default ({
	name: 'Error404',
	components: {
		NcButton,
		NcEmptyContent,
		NcIconSvgWrapper,
		Home,
	},
	data() {
		return {
			linkInstance: 'https',
			mdiFolderAlert,
		}
	},
	created() {
		const url = generateUrl('/')
		this.linkInstance = url
	},
})
</script>
