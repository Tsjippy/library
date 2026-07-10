<?php

namespace TSJIPPY\LIBRARY;

/**
 * Plugin Name:          Tsjippy Library
 * Description:          This plugin adds the possibility to scan book from pictures and add them to the library
 * Version:              10.4.4
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    7.0
 * Requires PHP:         8.3
 * Tested up to:         7.0
 * Plugin URI:            https://github.com/Tsjippy/library
 * Tested:                7.0
 * TextDomain:            tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', basename(__FILE__, '.php'));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_library_settings', []));

const METAS = [
    'subtitle'  => 'text',
    'author'    => 'array',
    'series'    => 'text',
    'year'      => 'number',
    'languague' => 'text',
    'age'       => 'text',
    'pages'     => 'number',
    'image'     => 'text',
    'location'  => 'array',
    'url'       => 'url',
];

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// run right before activation
register_activation_hook(__FILE__, function () {
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});