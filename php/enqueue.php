<?php
namespace SIM\LIBRARY;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets() {
    wp_register_style('sim_library_style', SIM\pathToUrl(MODULE_PATH.'css/library.min.css'), array(), MODULE_VERSION);
	wp_register_script('sim_library_script', SIM\pathToUrl(MODULE_PATH.'js/library.min.js'), ['sim_formsubmit_script'], MODULE_VERSION, true);
}