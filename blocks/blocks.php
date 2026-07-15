<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks()
{
    register_block_type(
        'tsjippy-library/promote-book',
        array(
            'title'           => __( 'Random Book', '%TEXTDOMAIN%' ),
            'attributes'      => array(
                'default-message'   => array(
                    'label'   => __( 'Default Message', '%TEXTDOMAIN%' ),
                    'type'    => 'string',
                    'default' => '',
                )
            ),
            'render_callback' => __NAMESPACE__.'\randomBook',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'book (alt)'
        )
    );
}

/**
 * Display the book of the day on the front page.
 */
function randomBook($attributes)
{
    $books  = bookOfTheDay();

    if ($books) {
        extract($books);
    }
    
    if (!$books || !$title || !$image || !$url) {
        if(($_REQUEST['action'] ?? $_REQUEST['context'] ?? '') == 'edit'){
            return "<div class='warning'>No Book to display</div>";
        }elseif($attributes['default-message'] ?? false){
            return "<div>".$attributes['default-message']."</div>";
        }

        return;
    }

    wp_enqueue_style('tsjippy_library_frontpage', TSJIPPY\pathToUrl(PLUGINPATH . 'css/frontpage.min.css'), array(), PLUGINVERSION);

    ob_start();
    ?>
    <div id='book-of-the-day'>
        <h3 style='text-align: center;color: #444;font-weight: 500;'>
            Book of the day
        </h3>
        <p>
            <a href='<?php echo esc_url($url); ?>' target='_blank'>
                <div style='text-align:center;'>
                    <img width='200' height='200' src='<?php echo esc_attr($image); ?>' loading='lazy' class='book-cover' alt='book cover' decoding='async' />
                </div>

                <h4>
                    <?php echo esc_html($title); ?>
                </h4>
            </a>
            <?php echo wp_kses_post($description); ?>
            <br>
            <br>
            <strong>Find it in the library at:</strong>
            <?php echo wp_kses_post(implode(' & ', $locations)); ?>.
        </p>
    </div>
    <?php
    return ob_get_clean();
}