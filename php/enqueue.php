<?php
namespace TSJIPPY\LIBRARY;
use TSJIPPY;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets() {
    wp_register_style('tsjippy_library_style', TSJIPPY\pathToUrl(PLUGINPATH.'css/library.min.css'), array(), MODULE_VERSION);
	wp_register_script('tsjippy_library_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/library.min.js'), ['tsjippy_formsubmit_script'], MODULE_VERSION, true);
}