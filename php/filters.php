<?php
namespace SIM\LIBRARY;
use SIM;

add_filter( 'post_thumbnail_html', __NAMESPACE__.'\setExternalImage', 999, 5 );
function setExternalImage($html, $postId, $postThumbnailId, $size, $attr ){
    return $html;
}


// Make sure the template path includes the library folder, not the books folder
add_filter('sim-template-filter', __NAMESPACE__.'\changeModuleName');
function changeModuleName($templateFile){
    return str_replace('/books/', '/library/', $templateFile);
}

add_action ( 'wp_ajax_process_library_upload', __NAMESPACE__.'\ajaxUploadFiles');
function ajaxUploadFiles(){
    global $Modules;

    if(!empty($_FILES['files'])){
        $library		= getLibrary($Modules[MODULE_SLUG]);
        $files	        = $_FILES['files'];
        $result         = '';

        foreach($files['tmp_name'] as $key => $path){
            if(is_file($path)){
                $result         .= $library->processImage($path);
            }
        }
        
        if($result){ 
            wp_send_json_success($result);
        }else{
            wp_send_json_error('Failed to process the image');
        }
    }else{
        wp_send_json_error('No files uploaded');
    }
}

function includeBooksInSearch($query) {
    if ($query->is_search && !is_admin()) {
        $query->set('post_type', array('post', 'page', 'book'));
    }
    return $query;
}
add_filter('pre_get_posts', __NAMESPACE__.'\includeBooksInSearch');

/**
 * Register a 'genre' taxonomy for post type 'book', with a rewrite to match book CPT slug.
 *
 * @see register_post_type for registering post types.
 */
function createAuthorTax() {
    $taxonomyName		= 'authors';
	$plural				= 'Authors';
    $single             = 'author';

	/*
		CREATE CATEGORIES
	*/
	$labels = array(
		'name' 							=> "$plural Types",
		'singular_name' 				=> "$plural Types",
		'search_items' 					=> "Search $plural Types",
		'popular_items' 				=> "Popular $plural Types",
		'all_items' 					=> "All $plural Types",
		'parent_item' 					=> "Parent $single Type",
		'parent_item_colon' 			=> "Parent $single Type:",
		'edit_item' 					=> "Edit $single Type",
		'update_item' 					=> "Update $single Type",
		'add_new_item' 					=> "Add New $single Type",
		'new_item_name' 				=> "New $single Type Name",
		'separate_items_with_commas' 	=> "Separate $single type with commas",
		'add_or_remove_items' 			=> "Add or remove $single type",
		'choose_from_most_used' 		=> "Choose from the most used $single types",
		'menu_name' 					=> ucfirst($single)." Categories",
	);
	
	$args = array(
		'labels' 			=> $labels,
		'public' 			=> true,
		'show_ui' 			=> true,
		'show_in_rest' 		=> true,
		'hierarchical' 		=> true,
		'rewrite' 			=> array(
			'slug' 			=> $taxonomyName,	//archive pages on /plural/
			'hierarchical' 	=> true,
			'has_archive'	=> true
		),
		'query_var' 		=> true,
		'singular_label' 	=> "$plural Type",
		'show_admin_column' => true,
	);
	
	//register taxonomy category
	register_taxonomy( $taxonomyName, 'book', $args );

	//redirect plural to archive page as well
	add_rewrite_rule($taxonomyName.'/?$','index.php?post_type=book','top');

	// Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
}
add_action( 'init', __NAMESPACE__.'\createAuthorTax', 0 );
