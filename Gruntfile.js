/**
 * Grunt tasks.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig( {
		// Package.
		pkg: grunt.file.readJSON( 'package.json' ),

		// JSHint.
		jshint: {
			all: [ 'Gruntfile.js', 'composer.json', 'package.json' ]
		},

		// PHP Code Sniffer.
		phpcs: {
			application: {
				src: [
					'**/*.php',
					'!node_modules/**',
					'!vendor/**',
					'!wordpress/**',
					'!wp-content/**'
				]
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'phpcs.xml.dist',
				showSniffCodes: true
			}
		},

		// PHPLint.
		phplint: {
			all: [ 'src/**/*.php' ]
		},

		// PHP Mess Detector.
        phpmd: {
            application: {
                dir: 'src'
            },
            options: {
                bin: 'vendor/bin/phpmd',
                exclude: 'node_modules',
                reportFormat: 'xml',
                rulesets: 'phpmd.xml.dist'
            }
        },

		// PHPUnit.
        phpunit: {
            options: {
                bin: 'vendor/bin/phpunit'
            },
            application: {}
        }
	} );

	// Default task(s).
	grunt.registerTask( 'default', [ 'jshint', 'phplint', 'phpmd', 'phpcs', 'phpunit' ] );
};
