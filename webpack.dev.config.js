const { merge } = require('webpack-merge')
const webpackConfig = require('@nextcloud/webpack-vue-config')

module.exports = merge(webpackConfig, {
	devtool: 'source-map'
})

