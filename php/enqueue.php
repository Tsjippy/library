<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
/**
 * Load assets for the library.
 *
 * @return void
 */
function loadAssets()
{
    wp_register_style('tsjippy_library_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/library.min.css'), array(), PLUGINVERSION);

    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions', 
        "@tsjippy/form_exports", 
        "@tsjippy/show_loader", 
        "@tsjippy/display_message"
    ] :
    [];
    wp_register_script_module('@tsjippy/library_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/library' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);

    add_filter( 'script_module_data_@tsjippy/library_script', function($data){
        $data['ajaxUrl'] = admin_url( 'admin-ajax.php' );

        return $data; 
    } );
}
