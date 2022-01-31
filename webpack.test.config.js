/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/* eslint-disable node/no-extraneous-require */

const path = require('path')
const { merge } = require('webpack-merge')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const nodeExternals = require('webpack-node-externals')

require('jsdom-global')('', {
	url: 'http://localhost',
})

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
