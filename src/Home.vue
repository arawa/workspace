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
	<NcContent id="content" app-name="workspace">
		<LeftSidebar />
		<WorkspaceContent />
	</NcContent>
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import LeftSidebar from './LeftSidebar.vue'
import WorkspaceContent from './WorkspaceContent.vue'

export default {
	name: 'Home',
	components: {
		NcContent,
		LeftSidebar,
		WorkspaceContent,
	},
	beforeCreate() {
		if (this.$root.$data.canAccessApp === 'false') {
			this.$router.push({
				path: '/unauthorized',
			})
		}
	},
}
</script>

