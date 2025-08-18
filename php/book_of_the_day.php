<?php
namespace SIM\LIBRARY;
use SIM;    

/**
 * Filter to ensure that the post content is not empty
 */
function filterWhereContentNotEmpty( $where = '' ) {
    $where .= " AND post_content IS NOT NULL AND post_content != ''";

    return $where;
}

/**
* Book of the day
* 
* Gets a random book which has a picture and a description

* @return array|boolean   Array of text, base64 image and url, or false if there are not enough books
*/
function bookOfTheDay(){
    global $post;

    // get random book post with a picture and description
    add_filter( 'posts_where', __NAMESPACE__.'\filterWhereContentNotEmpty' );
    
    $books = get_posts(
        array(
            'post_type'         => 'book',
            'numberposts'       => -1,
            'orderby'           => 'rand',
            'post_status'       => 'publish',
            'suppress_filters'  => false,
            'meta_query'        => array(
                'relation'      => 'AND',
                array(
                    'key'       => 'image',
                    'compare'   => 'EXISTS',                 
                ),
                array(
                    'key'     => 'image',
                    'value'   => '',
                    'compare' => '!=',
                ),
                array(
                    'key'       => 'url',
                    'compare'   => 'EXISTS',                 
                ),
                array(
                    'key'     => 'url',
                    'value'   => '',
                    'compare' => '!=',
                ),
            ),
        )
    );
    
    remove_filter( 'posts_where', __NAMESPACE__.'\filterWhereContentNotEmpty' );

    // do not continue if we have less then 100 books
    if(count($books) < 100){
        return false;
    }

    // Only use the first book
    $book       = $books[0];
    
    // add the book picture
    $id        = get_post_meta($book->ID, 'image', true);
    if(!empty($id)){
        $size	= 'M';
        $url	= "https://covers.openlibrary.org/b/id/$id-$size.jpg";
    }
    
    $imageData  = file_get_contents($url);
    
    if ($imageData){
        $tempFile   = tempnam(sys_get_temp_dir(), 'mime_check_');

        $mimeType   = '';

        if (file_put_contents($tempFile, $imageData)) {
            $mimeType   = mime_content_type($tempFile);
        } 
        unlink($tempFile);
        
        if ($imageData !== false && !empty($mimeType)) {
            $base64Image    = base64_encode($imageData);
            $picture        = 'data:' . $mimeType . ';base64,' . $base64Image;
        }
    }
    
    // add the url to the args
    $url    = get_permalink($book);

    $post   = $book; // Needed to ensure that the post is set for the excerpt function
    $description = get_the_excerpt($book);
    wp_reset_postdata();
    
    return [
        'description'   => $description,
        'title'         => $book->post_title,
        'image'         => $picture,
        'url'           => $url
    ];
}