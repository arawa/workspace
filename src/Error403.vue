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
	<div class="error403">
		<h1 class="h-400">
			{{ t('workspace', 'Error 403') }}
		</h1>
		<p class="p-400">
			{{ t('workspace', 'You aren\'t allowed to access into this application !') }}
		</p>
		<p class="p-400">
			<span>
				<a class="link-to-home" :href="linkInstance">{{ t('workspace', 'return to home') }}</a>
			</span>
		</p>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default ({
	name: 'Error403',
	components: {
	},
	data() {
		return {
			linkInstance: 'https',
		}
	},
	created() {
		const url = generateUrl('/')
		this.linkInstance = url
	},
})
</script>

<style scoped>

.error403 {
	height: auto;
	width: auto;
	text-align: center;
	margin: 100px;
	margin-left: auto;
	margin-right: auto;
}

.h-400 {
	font-size: 40px;
	font-weight: bold;
}

.p-400 {
	margin-top: 30px;
	font-size: 20px;
	font-style: italic;
}

.link-to-home {
	text-decoration: underline;
	color: var(--color-primary);
}
</style>
