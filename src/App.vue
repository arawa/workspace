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
	<router-view />
</template>

<script>
export default {
	name: 'App',
	created() {
		this.$root.$data.isUserGeneralAdmin = document.getElementById('isUserGeneralAdmin').value
		this.$root.$data.canAccessApp = document.getElementById('canAccessApp').value
		this.$root.$data.aclInheritPerUser = document.getElementById('aclInheritPerUser').value
		const aclInheritPerUser = document.getElementById('aclInheritPerUser').value
		// eslint-disable-next-line no-console
		aclInheritPerUser ? console.log('workspace: h1') : console.log('workspace: h2')
	},
}

</script>
