<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

/**
 * The content of a book shared between a single post, archive or the recipes page.
 **/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$archive    = false;
if (is_tax() || is_archive()) {
    $archive    = true;
}

wp_enqueue_style('tsjippy_library_template', TSJIPPY\pathToUrl(TSJIPPY\PLUGINPATH . 'css/template.min.css'), array(), PLUGINVERSION);

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div
        class="cat-card
            <?php if ($archive) {
                echo ' inside-article';
            } ?>">
        <?php
        if ($archive) {
            $url = get_permalink(get_the_ID());
            echo the_title("<h3 class='archivetitle'><a href='" . esc_url($url) . "'>", '</a></h3>');
        } else {
            do_action('tsjippy-before-content');
        }
        ?>
        <div
            class='entry-content
            <?php if ($archive) {
                echo ' archive';
            } ?>'>
            <?php
            $id            = get_post_meta(get_the_ID(), 'tsjippy_image', true);

            if (!empty($id)) {
                $size    = 'M';
                $url    = "https://covers.openlibrary.org/b/id/$id-$size.jpg";

            ?>
                <div class='picture'>
                    <img src='<?php echo esc_url($url); ?>' class='book-image' loading='lazy'>
                </div>
            <?php
            }

            ?>
            <div class='book-description'>
                <?php

                if (is_user_logged_in()) {
                ?>
                    <div class='author'>
                        Shared by: 
                        <a href='<?php echo esc_url(TSJIPPY\maybeGetUserPageUrl(get_the_author_meta('ID'))); ?>'>
                            <?php echo esc_html(get_the_author()); ?>
                        </a>
                    </div>
                <?php
                }

                ?>
                <div class='book metas'>
                    <?php
                    $categories = wp_get_post_terms(
                        get_the_ID(),
                        'books',
                        array(
                            'orderby'   => 'name',
                            'order'     => 'ASC',
                            'fields'    => 'id=>name'
                        )
                    );

                    if (!empty($categories)) {
                    ?>
                        <div class='category book meta'>
                            <h4>Genres</h4>
                            <?php
                            $url    = plugins_url('pictures/category.png', PLUGIN);
                            ?>
                            <img src='<?php echo esc_url($url); ?>' alt='category' loading='lazy' class='book-icon'>
                            <?php

                            //First loop over the cat to see if any parent cat needs to be removed
                            foreach ($categories as $id => $category) {
                                //Get the child categories of this category
                                $children = get_term_children($id, 'books');

                                //Loop over the children to see if one of them is also in he cat array
                                foreach ($children as $child) {
                                    if (isset($categories[$child])) {
                                        unset($categories[$id]);
                                        break;
                                    }
                                }
                            }

                            //now loop over the array to print the categories
                            $lastKey     = array_key_last($categories);
                            foreach ($categories as $id => $category) {
                                //Only show the category if all of its subcats are not there
                                $url      = get_term_link($id);
                                $category = ucfirst($category);

                            ?>
                                <a href='<?php esc_url($url); ?>' target='_blank'>
                                    <?php echo wp_kses_post($category); ?>
                                </a>
                            <?php

                                if ($id != $lastKey) {
                                    echo ', ';
                                }
                            }
                            ?>
                        </div>
                    <?php
                    }

                    foreach (METAS as $meta => $type) {
                        if ($meta == 'tsjippy_image') {
                            continue;
                        }

                        $single       = true;
                        if ($type == 'array') {
                            $single   = false;
                        }

                        $value        = get_post_meta(get_the_ID(), 'tsjippy_' . $meta, $single);

                        if (empty($value)) {
                            continue;
                        }

                        if ($type == 'url') {
                            $value = "<a href='$value' target='_blank'>" . basename($value) . "</a>";
                        } elseif ($meta == 'author' || $meta == 'location') {
                            $taxonomy = $meta == 'author' ? 'authors' : 'book-locations';

                            $terms = wp_get_post_terms(
                                get_the_ID(),
                                $taxonomy,
                                array(
                                    'orderby'   => 'name',
                                    'order'     => 'ASC',
                                    'fields'    => 'id=>name'
                                )
                            );

                            $links    = [];
                            foreach ($terms as $id => $termName) {
                                if ($meta == 'author') {
                                    $splittedName     = explode(', ', $termName);

                                    if (count($splittedName) > 1) {
                                        $lastName         = $splittedName[0];
                                        $firstnames     = implode(' ', array_slice($splittedName, 1));
                                        $termName             = "$firstnames $lastName";
                                    } else {
                                        $termName = ucfirst($termName);
                                    }
                                }

                                if (empty($termName)) {
                                    continue;
                                }

                                $url        = get_category_link($id);
                                $links[]    = "<a href='$url' target='_blank'>$termName</a>";
                            }

                            $value = implode('<br>', $links);
                        } elseif (is_array($value)) {
                            if (is_array($value[0])) {
                                //If the value is an array of arrays, we need to implode the inner arrays
                                foreach ($value as $index => $innerValue) {
                                    $value[$index] = implode(', ', $innerValue);
                                }
                            }
                            $value = implode('<br>', $value);
                        }

                        $imageUrl     = TSJIPPY\pathToUrl(PLUGINPATH . "pictures/{$meta}.png");

                    ?>
                        <div class='$meta book meta'>
                            <div class='flex meta-wrapper'>
                                <img src='<?php echo esc_url($imageUrl); ?>' alt='<?php echo esc_attr($meta); ?>' loading='lazy' class='book-icon' title='<?php echo esc_attr($meta); ?>'>
                                <div>
                                    <?php echo wp_kses_post($value); ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }

                    do_action('tsjippy-library-inside-book-metas');
                    ?>
                </div>

                <div class="description book">
                    <?php
                    //Only show summary on archive pages
                    if ($archive) {
                        $excerpt = force_balance_tags(wp_kses_post(get_the_excerpt()));
                        if (empty($excerpt)) {
                            $url = get_permalink();
                            ?>
                            <br>
                            <a href='<?php echo esc_url($url);?>'>
                                View description »
                            </a>
                            <?php
                        } else {
                            echo wp_kses_post($excerpt);
                        }
                        //Show everything including category specific content
                    } else {
                        if (empty($post->post_content)) {
                            /** @disregard $P1008 */ 
                            echo wp_kses_post(apply_filters('tsjippy-empty-description', 'No content found... ', $post));
                        }

                        the_content();
                    }

                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">Pages:',
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div>
            </div>
        </div>
    </div>
</article>