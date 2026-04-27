<?php
namespace TSJIPPY\LIBRARY;

/**
 * Plugin Name:  		Tsjippy Library
 * Description:  		This plugin adds the possibility to scan book from pictures and add them to the library
 * Version:      		1.0.0
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.3
 * Requires PHP: 		8.3
 * Tested up to: 		6.9
 * Plugin URI:			https://github.com/Tsjippy/library
 * Tested:				6.9
 * TextDomain:			tsjippy
 * Requires Plugins:	tsjippy-shared-functionality
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pluginData = get_plugin_data(__FILE__, false, false);

// Define constants
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGINPATH', __DIR__.'/');
define(__NAMESPACE__ .'\PLUGINVERSION', $pluginData['Version']);
define(__NAMESPACE__ .'\PLUGINSLUG', basename(__FILE__, '.php'));
define(__NAMESPACE__ .'\SETTINGS', get_option('tsjippy_library_settings', []));

const METAS = [
    'subtitle'  		=> 'text',
    'author'   			=> 'array',
    'series'    		=> 'text',
    'year'      		=> 'number',
    'languague' 		=> 'text',
    'age'       		=> 'text',
    'pages'     		=> 'number',
	'image'				=> 'text',
	'location'			=> 'array',
	'url'				=> 'url',
];

// run on activation
register_activation_hook( __FILE__, function(){
} );

// run on deactivation
register_deactivation_hook( __FILE__, function(){
} );