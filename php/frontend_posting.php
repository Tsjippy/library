<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-frontend-content-post-content-title', __NAMESPACE__ . '\contentTitle');
/**
 * Display content title for book post type
 * 
 * @param   string  $postType   The post type of the current post
 */
function contentTitle($postType)
{
    // Book content title
    $class = 'property book';
    if ($postType != 'book') {
        $class .= ' hidden';
    }

?>
    <h4 class='<?php echo esc_attr($class); ?>' name='book-content-label'>
        Please describe the book
    </h4>
<?php
}

/**
 * Allow comments
 * 
 * @param   \WP_Post    $post       The new or updated post
 * @param   object      $object     FrontEndContent Instance
 * @param   array       $request    The sanitized request data
 */
add_action('tsjippy-frontend-content-after-post-save', __NAMESPACE__ . '\afterPostSave', 10, 3);
/**
 * Update the book metas for all books in the library.
 * 
 * @param   \WP_Post    $post       The new or updated post
 * @param   object      $object     FrontEndContent Instance
 * @param   array       $request    The sanitized request data
 */
function afterPostSave($post, $object, $request)
{
    if ($post->post_type != 'book') {
        return;
    }

    $library        = new Library();

    foreach (METAS as $meta => $type) {
        if (isset($request[$meta])) {
            if (empty($request[$meta])) {
                delete_post_meta($post->ID, "tsjippy_$meta");
            } elseif ($type == 'array') {
                $curValues = get_post_meta($post->ID, 'tsjippy_' . $meta);
                $newValues = $request[$meta];

                $deleted  = array_diff($curValues, $newValues);
                foreach ($deleted as $value) {
                    delete_metadata('post', $post->ID, 'tsjippy_' . $meta, $value);

                    if ($meta == 'author' || $meta == 'location') {
                        $taxonomy = $meta == 'author' ? 'authors' : 'book-locations';

                        //Remove author or location from the post
                        $term = get_term_by('name', $value, $taxonomy);

                        wp_remove_object_terms($post->ID, $term->term_id, $taxonomy);
                    }
                }

                $added    = array_diff($newValues, $curValues);
                foreach ($added as $value) {
                    if ($meta == 'author') {
                        $library->processAuthor($value, $post->ID);
                    } else {
                        add_metadata('post', $post->ID, 'tsjippy_' . $meta, $value);

                        if ($meta == 'location') {
                            wp_set_post_terms($post->ID, [$value], 'book-locations', true);
                        }
                    }
                }
            } else {
                //Store value
                update_metadata('post', $post->ID, 'tsjippy_' . $meta, $request[$meta]);
            }
        }
    }
}

//add meta data fields
add_action('tsjippy-frontend-content-post-before-default-options-content', __NAMESPACE__ . '\afterPostContent', 20, 2);
/**
 * Display book meta data fields
 * 
 * @param   object  $object    FrontEndContent Instance
 */
function afterPostContent($object)
{

    if (!empty($object->post) && $object->post->post_type != 'book') {
        return;
    }

    //Load js
    wp_enqueue_script('tsjippy_book_script');

    $postId     = $object->postId;
    $postName   = $object->postName;

?>
    <div
        id="book-attributes"
        class="property book
    <?php if ($postName != 'book') echo ' hidden'; ?>">
        <input type='hidden' class='no-reset' name='static-content' value='static-content'>

        <fieldset id="book" class="frontend-form">
            <legend>
                <h4>
                    Book details
                </h4>
            </legend>

            <table class="table left no-border">
                <?php
                foreach (METAS as $meta => $type) {
                    if ($type == 'url') {
                        $type   = 'text';
                    }

                    switch ($meta) {
                        default:
                            $text   = $meta;
                    }

                ?>
                    <tr>
                        <th>
                            <label for="<?php echo esc_attr($meta); ?>">
                                <?php echo esc_html(ucfirst($text)); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            if ($type == 'array') {
                                $type   = 'text';

                                $values = get_post_meta($postId, 'tsjippy_' . $meta);
                                if (empty($values)) {
                                    $values = [];
                                }

                            ?>
                                <div class="clone-divs-wrapper">
                                    <?php
                                    foreach ($values as $index => $value) {
                                        if (is_array($value)) {
                                            $value = implode(',', $value);
                                        }

                                    ?>
                                        <div id="<?php echo esc_attr($meta); ?>-div-<?php echo esc_attr($index); ?>" class="clone-div" data-div-id="<?php echo esc_attr($index); ?>">
                                            <div class='button-wrapper'>
                                                <input type='<?php echo esc_attr($type); ?>' class='formbuilder' name='<?php echo esc_attr($meta); ?>[<?php echo esc_attr($value); ?>]' value='<?php echo esc_attr($value); ?>' style='width: calc(100% - 70px);'>
                                                <button type="button" class="add button" style="flex: 1;">+</button>
                                                <button type="button" class="remove button" style="flex: 1;">-</button>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            <?php
                            } else {
                                $value = get_post_meta($postId, 'tsjippy_' . $meta, true);
                            ?>
                                <input type='<?php echo esc_attr($type); ?>' class='formbuilder' name='<?php echo esc_attr($meta); ?>' value='<?php echo esc_attr($value); ?>'>
                            <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </fieldset>
    </div>
<?php
}
