<?php
namespace SIM\LIBRARY;
use SIM;    

function filterWhereContentNotEmpty( $where = '' ) {
        $where .= " AND post_content IS NOT NULL AND post_content != ''";
        return $where;
    }

/**
* Gets a random book which has a picture and a description
* @return array|boolean   Array of text, base64 image and url, or false if there are not enough books
**\
function bookOfTheDay(){
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
        return false;
    }
    $book = $books[0];

    // create the text description
    $msg = "Book Of The Day<br><br>$book->post_title."<br><br>".$book->post_content;
    
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
        $picture = 'data:' . $mimeType . ';base64,' . $base64Image;
    }
    
    // add the url to the args
    $url = get_permalink($book);
    
    return [
        'message' => $msg,
        'image' => $image,
        'url' => $url
    ];
}