const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
const path          = require( 'path' );
const CopyPlugin    = require( "copy-webpack-plugin" );

// Configuration object.
const config = {
	...defaultConfig,
	entry: {
		'../v2/admin/admin': './assets/admin/index.js',
		'../v2/admin/settings-models': './assets/admin/models.js',
		'../v2/public/public': './assets/public/index.js',
		'../blocks/avatar-profile/avatar-profile': './assets/blocks/avatar-profile/index.js',
		'../blocks/grid-info/grid-info': './assets/blocks/grid-info/index.js',
		'../blocks/grid-status/grid-status': './assets/blocks/grid-status/index.js',
		'../blocks/popular-places/popular-places': './assets/blocks/popular-places/index.js',
		'../blocks/web-search/web-search': './assets/blocks/web-search/index.js',
		// '../blocks/events/index': './assets/blocks/events/index.js',
	},
	output: {
		filename: '[name].js',
		// Specify the path to the JS files.
		path: path.resolve( __dirname, 'build' ),
	},
}

// Export the config object.
module.exports = config;
