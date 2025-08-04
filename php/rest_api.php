<?php
namespace SIM\LIBRARY;
use SIM;
use WP_Error;

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInit');
function restApiInit() {

	//add_book
	register_rest_route(
		RESTAPIPREFIX.'/library',
		'/add_book',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addBook',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'title'		=> array(
					'required'	=> true
				),
				'summary'	=> array(
					'required'	=> true
				),
				'author'	=> array(
					'required'	=> true
				)
			)
		)
	);
}

function addBook(){
	global $Modules;

	$library		= getLibrary($Modules[MODULE_SLUG]);

	$result	= $library->createBook($_POST);
	
	return $result;
}