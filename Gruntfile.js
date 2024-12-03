const copy = require( "grunt-contrib-copy" );
const fs   = require( 'fs' );
const path = require( 'path' );

module.exports = function ( grunt ) {

	'use strict';

	// Function to get the current WordPress version from the installation
	function getCurrentWordPressVersion() {
		const wpVersionFile = path.join( __dirname, '../../../wp-includes/version.php' );
		if (fs.existsSync( wpVersionFile )) {
			const content = fs.readFileSync( wpVersionFile, 'utf8' );
			const match   = content.match( /\$wp_version = '([^']+)'/ );
			return match ? match[1] : null;
		}
		return null;
	}

	// Function to get the minimum required PHP version from the installation
	function getMinimumPHPVersion() {
		const wpVersionFile = path.join( __dirname, '../../../wp-includes/version.php' );
		if (fs.existsSync( wpVersionFile )) {
			const content = fs.readFileSync( wpVersionFile, 'utf8' );
			const match   = content.match( /\$required_php_version = '([^']+)'/ );
			return match ? match[1] : null;
		}
		return null;
	}

	// Project configuration
	grunt.initConfig(
		{

			pkg: grunt.file.readJSON( 'package.json' ),

			addtextdomain: {
				options: {
					textdomain: 'wrap',
				},
				update_all_domains: {
					options: {
						updateDomains: true
					},
					src: [ '*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*' ]
				}
			},

			wp_readme_to_markdown: {
				your_target: {
					files: {
						'readme.txt': 'readme-temp1-unknown',
					}
				},
			},

			concat: {
				md: {
					options: {
						separator: '\n\n', // Ajoutez une nouvelle ligne entre les fichiers
					},
					src: ['README.md', 'CHANGELOG.md', 'FAQ.md'],
					dest: 'readme-temp-md-1-concat.md',
				},
				combine: {
					options: {
						separator: '\n',
					},
					src: ['readme-temp-header-2-update.txt', 'readme-temp-md-3-clean.txt'],
					dest: 'readme.txt',
				},
			},
			replace: {
				format: {
					src: ['readme-temp-md-1-concat.md'],
					dest: 'readme-temp-md-2-format.txt',
					replacements: [{
						from: /^#  *(.*) *$/gm,
						to: '=== $1 ==='
					}, {
						from: /^##  *(.*) *$/gm,
						to: '== $1 =='
					}, {
						from: /^###  *(.*) *$/gm,
						to: '= $1 ='
					}, {
						from: /^- /gm,
						to: '* '
					}]
				},
				updateinfo: {
					src: ['readme-temp-header-1-copy.txt'],
					dest: 'readme-temp-header-2-update.txt',
					replacements: [{
						from: /Plugin Name: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Plugin Name: (.*)/ );
							return match ? `Plugin Name: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Plugin URI: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Plugin URI: (.*)/ );
							return match ? `Plugin URI: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Description: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Description: (.*)/ );
							return match ? `Description: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Version: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Version: (.*)/ );
							return match ? `Version: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Author: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Author: (.*)/ );
							return match ? `Author: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Author URI: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Author URI: (.*)/ );
							return match ? `Author URI: ${match[1]}` : matchedWord;
						}
					}, {
						from: /License: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /License: (.*)/ );
							return match ? `License: ${match[1]}` : matchedWord;
						}
					}, {
						from: /License URI: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /License URI: (.*)/ );
							return match ? `License URI: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Requires at least: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Requires at least: (.*)/ );
							return match ? `Requires at least: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Requires PHP: .*/g,
						to: function (matchedWord) {
							const content = grunt.file.read( 'wrap.php' );
							const match   = content.match( /Requires PHP: (.*)/ );
							return match ? `Requires PHP: ${match[1]}` : matchedWord;
						}
					}, {
						from: /Tested up to: .*/g,
						to: function (matchedWord) {
							const version = getCurrentWordPressVersion();
							return version ? `Tested up to: ${version}` : matchedWord;
						}
					}, {
						from: /^\n\n*(.*)\n*\n==/gm,
						to: function (match, p1) {
							const content          = grunt.file.read( 'wrap.php' );
							const descriptionMatch = content.match( /Description: (.*)/ );
							return descriptionMatch ? `\n${descriptionMatch[1]}\n\n==` : `\n${p1}\n\n==`;
						}
					}]
				},
				removeTitle: {
					src: ['readme-temp-md-2-format.txt'],
					dest: 'readme-temp-md-3-clean.txt',
					replacements: [{
						from: /^=== .* ===\n*/,
						to: ''
					}]
				}
			},

			copy: {
				header: {
					src: 'readme.txt',
					dest: 'readme-temp-header-1-copy.txt',
					options: {
						process: function (content) {
							const parts = content.split( '== Description ==' );
							return parts[0] + '== Description ==\n';
						}
					}
				},
				append: {
					src: 'readme-temp-md-2-format.txt',
					dest: 'readme-temp-header-2-update.txt',
					options: {
						process: function (content) {
							const existingContent = grunt.file.read( 'readme-temp-header-2-update.txt' );
							return existingContent + '\n\n' + content;
						}
					}
				},
			},

			clean: {
				temp: ['readme-temp-header-1-copy.txt', 'readme-temp-md-1-concat.md', 'readme-temp-md-2-format.txt', 'readme-temp-header-2-update.txt', 'readme-temp-md-3-clean.txt']
			},

			makepot: {
				target: {
					options: {
						domainPath: '/languages',
						exclude: [ '\.git/*', 'bin/*', 'node_modules/*', 'tests/*' ],
						mainFile: 'wrap.php',
						potFilename: 'wrap.pot',
						potHeaders: {
							poedit: true,
							'x-poedit-keywordslist': true
						},
						type: 'wp-plugin',
						updateTimestamp: true
					}
				}
			},

		}
	);

	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-text-replace' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'default', [ 'i18n','readme' ] );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask(
		'readme',
		[
		'copy:header',
		'replace:updateinfo',
		'concat:md',
		'replace:format',
		'replace:removeTitle',
		'concat:combine',
		'clean:temp'
		]
	);

	grunt.util.linefeed = '\n';

};
