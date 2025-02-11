const copy = require( "grunt-contrib-copy" );
const fs   = require( 'fs' );
const path = require( 'path' );

module.exports = function ( grunt ) {

	'use strict';

	const pluginName = 'w4os';

    // Project configuration
	// grunt.initConfig( {
    //     pkg: grunt.file.readJSON( 'package.json' ),
	// } );

    // Load shared custom tasks in plugin
    require('./src/olib-dev/grunt-custom-tasks.js')(grunt, pluginName);

    grunt.util.linefeed = '\n';

};
