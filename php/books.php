<?php
namespace SIM\LIBRARY;
use SIM;

// Create the book custom post type
add_action('init', function(){
	SIM\registerPostTypeAndTax('book', 'books');
}, 999);

add_filter(	'widget_categories_args', __NAMESPACE__.'\widgetCats');
function widgetCats( $catArgs ) {
	//if we are on a books page, change to display the book types
	if(is_tax('books') || is_page('book') || get_post_type()=='book'){
		$catArgs['taxonomy'] 		= 'books';
		$catArgs['hierarchical']	= true;
		$catArgs['hide_empty'] 		= false;
	}
	
	return $catArgs;
}