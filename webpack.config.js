const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
const path          = require( 'path' );
const CopyPlugin    = require( "copy-webpack-plugin" );

// Configuration object.
const config = {
	...defaultConfig,
	entry: {
		'../v2/admin/admin': './src/admin/index.js',
		'../v2/admin/settings-models': './src/admin/models.js',
		'../v2/public/public': './src/public/index.js',
		'../blocks/avatar-profile/avatar-profile': './src/blocks/avatar-profile/index.js',
		'../blocks/grid-info/grid-info': './src/blocks/grid-info/index.js',
		'../blocks/grid-status/grid-status': './src/blocks/grid-status/index.js',
		'../blocks/popular-places/popular-places': './src/blocks/popular-places/index.js',
		'../blocks/web-search/web-search': './src/blocks/web-search/index.js',
		// '../blocks/events/index': './src/blocks/events/index.js',
	},
	output: {
		filename: '[name].js',
		// Specify the path to the JS files.
		path: path.resolve( __dirname, 'build' ),
	},
}

// Export the config object.
module.exports = config;
