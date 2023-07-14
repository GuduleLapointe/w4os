const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
const path          = require( 'path' );
const CopyPlugin    = require( "copy-webpack-plugin" );

// Configuration object.
const config = {
	...defaultConfig,
	entry: {
		'../includes/admin/admin': './src/admin/index.js',
		'../includes/admin/settings-models': './src/admin/models.js',
		'../includes/public/public': './src/public/index.js',
		'../blocks/popular-places/popular-places': './src/blocks/popular-places/index.js',
		'../blocks/avatar-profile/avatar-profile': './src/blocks/avatar-profile/index.js',
		'../blocks/grid-info/grid-info': './src/blocks/grid-info/index.js',
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
