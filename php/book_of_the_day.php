<?php
namespace SIM\LIBRARY;
use SIM;    

function filterWhereContentNotEmpty( $where = '' ) {
        $where .= " AND post_content IS NOT NULL AND post_content != ''";
        return $where;
    }

add_filter( 'posts_where', __NAMESPACE__.'\filterWhereContentNotEmpty' );

$selectedBook = get_posts(
            array(
                'post_type'              => 'book',
                'numberposts' => 1,
    'orderby'     => 'rand',
    'post_status' => 'publish',
    'meta_query'     => array(
        array(
            'key'     => 'picture',     // Replace with your custom field key
            'compare' => 'EXISTS',                 
        ),
        array(
            'key'     => 'url',     // Replace with your custom field key
            'compare' => 'EXISTS',                 
        ),
    ),
            )
        );

remove_filter( 'posts_where', __NAMESPACE__.'\filterWhereContentNotEmpty' );
