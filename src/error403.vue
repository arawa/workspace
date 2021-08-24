<template>
	<div class="error403">
		<h1 class="h-400">Error 403</h1>
		<p class="p-400">You shall not pass !</p>
		<p class="p-400"><span>Please, return in your instance : <a :href="linkInstance">{{ linkInstance }}</a></span></p>
	</div>
</template>

<script>
import { getRootUrl } from '@nextcloud/router'

export default ({
	name: 'error403',
	components: {
	},
	data() {
		return {
			key: 'value',
			linkInstance: 'http(s)',
		}
	},
	created() {
		const url = getRootUrl()
		this.linkInstance = url
	}
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
</style>
