/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license AGPL-3.0-or-later
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

const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpack = require('webpack')

webpackConfig.module.rules.push(
	{
		test: /\.svg$/i,
		resourceQuery: /raw/,
		type: 'asset/source',
	},
)
console.debug('coucou')
webpackConfig.plugins = webpackConfig.plugins.filter(
	p => p.constructor.name !== 'DefinePlugin',
)

webpackConfig.plugins.push(
	new webpack.DefinePlugin({
		__VUE_PROD_DEVTOOLS__: JSON.stringify(true),
		__VUE_OPTIONS_API__: JSON.stringify(true),
	}),
)

// process.env.NODE_ENV = 'production'
// webpackConfig.plugins.push(
// 	new webpack.DefinePlugin({
// 		// __VUE_OPTIONS_API__: 'true',
// 		__VUE_PROD_DEVTOOLS__: 'true',
// 	}),
// )

module.exports = webpackConfig
