<?php
namespace SIM\LIBRARY;
use SIM;

add_filter('sim_frontend_posting_modals', __NAMESPACE__.'\postingModals');
function postingModals($types){
    $types[]	= 'books';
    return $types;
}

add_action('sim_frontend_post_before_content', __NAMESPACE__.'\beforeContent');
function beforeContent($frontEndContent){
    $categories = get_categories( array(
        'orderby' 	=> 'name',
        'order'   	=> 'ASC',
        'taxonomy'	=> 'books',
        'hide_empty'=> false,
    ) );

    $frontEndContent->showCategories('book', $categories);
}

add_action('sim_frontend_post_content_title', __NAMESPACE__.'\contentTitle');
function contentTitle($postType){
    // Book content title
    $class = 'property book';
    if($postType != 'book'){
        $class .= ' hidden';
    }

    echo "<h4 class='$class' name='book_content_label'>";
        echo 'Please describe the book';
    echo "</h4>";
}

add_action('sim_after_post_save', __NAMESPACE__.'\afterPostSave', 10, 2);
function afterPostSave($post, $frontEndPost){
    if($post->post_type != 'book'){
        return;
    }

    //store categories
    $frontEndPost->storeCustomCategories($post, 'books');

    // ISBN
    if(isset($_POST['isbn'])){
        if(empty($_POST['isbn'])){
            delete_post_meta($post->ID, 'isbn');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'isbn', $_POST['isbn']);
        }
    }

    //url
    if(isset($_POST['url'])){
        if(empty($_POST['url'])){
            delete_post_meta($post->ID, 'url');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'url', $_POST['url']);
        }
    }
}

//add meta data fields
add_action('sim_frontend_post_after_content', __NAMESPACE__.'\afterPostContent', 10, 2);
function afterPostContent($frontendcontend){

    if(!empty($frontendcontend->post) && $frontendcontend->post->post_type != 'book'){
        return;
    }

    //Load js
    wp_enqueue_script('sim_book_script');

    $postId     = $frontendcontend->postId;
    $postName   = $frontendcontend->postName;
    $book   = get_post_meta($postId, 'book', true);
    if(!is_array($book) && !empty($book)){
        $book  = json_decode($book, true);
    }

    $url = get_post_meta($postId, 'url', true);
    ?>
    <style>
        .form-table, .form-table th, .form-table, td{
            border: none;
        }
        .form-table{
            text-align: left;
        }
    </style>
    <div id="book-attributes" class="property book<?php if($postName != 'book'){echo ' hidden';} ?>">
        <input type='hidden' name='static_content' value='static_content'>
            
        <fieldset id="book" class="frontendform">
            <legend>
                <h4>Location details</h4>
            </legend>

            <table class="form-table">
                <tr>
                    <th><label for="isbn">ISBN number</label></th>
                    <td>
                        <input type='isbn' class='formbuilder' name='isbn' value='<?php echo get_post_meta($postId, 'isbn', true); ?>'>
                    </td>
                </tr>
                <tr>
                    <th><label for="url">More info</label></th>
                    <td>
                        <input type='url' class='formbuilder' name='url' value='<?php echo $url; ?>'>
                    </td>
                </tr>
            </table>
        </fieldset>
    </div>
    <?php
}