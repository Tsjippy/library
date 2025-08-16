<?php
namespace SIM\LIBRARY;
use SIM;    

function filterWhereContentNotEmpty( $where = '' ) {
        $where .= " AND post_content IS NOT NULL AND post_content != ''";
        return $where;
    }


add_filter('sim_after_bot_payer', __NAMESPACE__.'\afterBotPrayer');
function afterBotPrayer($args){
    // get random book post with a picture and description
    add_filter( 'posts_where', __NAMESPACE__.'\filterWhereContentNotEmpty' );
    
    $books = get_posts(
        array(
            'post_type'              => 'book',
            'numberposts' => -1,
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

    // do not continue if we have less then 100 books
    if(count($books) < 100){
        return;
    }
    $book = $books[0];

    
    // create the text description
    $msg = $book->post_title."\n\n".$book->post_content;
    
    $args['message'] .= "\n\nHave you read this book? ";
    
    // add the book picture
    $url = get_post_meta($book->ID, 'picture', true);
    
    $temp_file = tempnam(sys_get_temp_dir(), 'mime_check_');

    $mimeType='';
    
    $imageData = file_get_contents($url);
    
    if (file_put_contents($temp_file, $imageData)) {
        $mimeType = mime_content_type($temp_file);
    } 
    unlink($temp_file);
    
    if ($imageData !== false && !empty($mimeType)) {
        $base64Image = base64_encode($imageData);
        $dataUri = 'data:' . $mimeType . ';base64,' . $base64Image;

        $args['pictures'][] = $dataUri;
    }
    
    // add the url to the args
    $args['urls'][] = get_permalink($book);
}