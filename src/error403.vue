<template>
	<div class="error403">
		<h1 class="h-400">{{ t('workspace', 'Error 403') }}</h1>
		<p class="p-400">{{ t('workspace', 'You aren\'t allowed to access into this application !') }}</p>
		<p class="p-400"><span>{{ t('workspace', 'Please, return in your instance :') }} <a class="link-to-home" :href="linkInstance">{{ t('workspace', 'return to home') }}</a></span></p>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default ({
	name: 'error403',
	components: {
	},
	data() {
		return {
			linkInstance: '#',
		}
	},
	created() {
		const url = generateUrl('/')
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

.link-to-home {
	text-decoration: underline;
	color: var(--color-primary);
}
</style>
