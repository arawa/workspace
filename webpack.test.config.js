const path = require('path')
const { merge } = require('webpack-merge')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const nodeExternals = require('webpack-node-externals')
require('jsdom-global')('', {url: 'http://localhost'})

const appName = process.env.npm_package_name

const config = merge(webpackConfig, {
	mode: 'development',
	devtool: 'inline-cheap-module-source-map',
	externals: [nodeExternals()],
	// Overrides the output config provided by Nextcloud as for some reason 
	// contenthash doesn't get appended to filenames
	output: {
		path: path.resolve('./js'),
		publicPath: '/js/',
		filename: `${appName}-[name].js`,
		chunkFilename: `${appName}-[name].js`,
	},
})

module.exports = config
