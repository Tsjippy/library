<?php
namespace SIM\LIBRARY;
use SIM;
use WP_Error;

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInit');
function restApiInit() {
	// add_category
	register_rest_route(
		RESTAPIPREFIX.'/library',
		'/add_category',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addCategory',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'cat_name'		=> array('required'	=> true),
				'cat_parent'	=> array(
					'required'	=> true,
					'validate_callback' => function($catParentId){
						return is_numeric($catParentId);
					}
				),
				'post_type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
			)
		)
	);

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

/**
 * Add a new category to a post type
 */
function addCategory(\WP_REST_Request $request ){
	$name		= $request->get_param('cat_name');
	$parent		= $request->get_param('cat_parent');
	$postType	= $request->get_param('post_type');

	$taxonomy	= get_object_taxonomies($postType)[0];
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)){
		$args['parent'] = $parent;
	}
	
	$result 	= wp_insert_term( ucfirst($name), $taxonomy, $args);

	if(is_wp_error($result)){
		return $result;
	}

	do_action('sim_after_category_add', $postType, strtolower($name), $result);
	
	if(is_wp_error($result)){
		return new \WP_Error('Event Cat error', $result->get_error_message(), ['status' => 500]);
	}else{
		return [
			'id'		=> $result['term_id'],
			'message'	=> "Added $name succesfully as a $postType category"
		];
	}
}

function addBook(){
	global $Modules;

	$library		= getLibrary($Modules[MODULE_SLUG]);

	$result	= $library->createBook($_POST);
	
	return $result;
}