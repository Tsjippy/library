<?php
namespace SIM\LIBRARY;
use SIM;

// Make sure the template path includes the library folder, not the books folder
add_filter('sim-template-filter', __NAMESPACE__.'\changeModuleName');
function changeModuleName($templateFile){
    return str_replace(['/books/', '/book-locations/', '/authors/'], '/library/', $templateFile);
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
 * Register a 'authors' taxonomy for post type 'book', with a rewrite to match book CPT slug.
 *
 * @see register_post_type for registering post types.
 */
function createBookTaxonomies($single) {
    $taxonomyName		= $single.'s';
	$single				= ucfirst(str_replace('book-', '', $single));
	$plural				= $single.'s';

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
		'menu_name' 					=> $plural,
	);
	
	$args = array(
		'labels' 			=> $labels,
		'public' 			=> true,
		'show_ui' 			=> true,
		'show_in_rest' 		=> true,
		'hierarchical' 		=> false,
		'rewrite' 			=> array(
			'slug' 			=> 'book/'.$taxonomyName,	//archive pages on /plural/
			'hierarchical' 	=> false,
			'has_archive'	=> true
		),
		'query_var' 		=> true,
		'singular_label' 	=> "$plural Type",
		'show_admin_column' => true,
	);
	
	//register taxonomy category
	register_taxonomy( $taxonomyName, 'book', $args );

	//redirect plural to archive page as well
	add_rewrite_rule('books/'.$taxonomyName.'/?$', "index.php?post_type=book&taxonomy_name=$taxonomyName",'top');

	// Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
}
add_action( 'init', function(){
	createBookTaxonomies('author');
	createBookTaxonomies('book-location');

	add_filter(
		'widget_categories_args',
		function ( $catArgs) {
			//if we are on a events page, change to display the event types
			if(is_tax('books') || is_page('book') || get_post_type() == 'book'){
				$catArgs['taxonomy'] 		= 'books';
				$catArgs['hierarchical']	= true;
				$catArgs['hide_empty'] 		= false;
			}
			
			return $catArgs;
		}
	);

	add_action('sim-updatemetas', function(){
		updateBookMetas();
	});
}, 0 );

add_filter('sim-theme-archive-page-title', __NAMESPACE__.'\changeArchiveTitle', 10, 2);
function changeArchiveTitle($title, $category){
	if($category->taxonomy == 'authors'){
		$splittedName 	= explode(', ', $category->name);

		if(count($splittedName) > 1){
			$lastName 		= $splittedName[0];
			$firstnames 	= implode(' ', array_slice($splittedName, 1));
			$title 			= "Books by $firstnames $lastName";
		}else{	
			$title = 'Books by '.ucfirst($category->name);
		}
	}elseif($category->taxonomy == 'book-locations'){
		$title = 'Books at '.ucfirst($category->name);
	}
	
	return $title;
}