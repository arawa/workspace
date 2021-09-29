<!--
  - @copyright 2021 Arawa <TODO>
  -
  - @author 2021 Cyrille Bollu <cyrille@bollu.be>
  -
  - @license <TODO>
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
	},
}

</script>
