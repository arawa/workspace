const nodeExternals = require('webpack-node-externals')

module.exports = {
	mode: 'development',
        devtool: 'inline-cheap-module-source-map',
	externals: [nodeExternals()]
}
