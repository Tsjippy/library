<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-theme-frontpage-before-main-content', __NAMESPACE__ . '\beforeMainContent', 30);
/**
 * Display the book of the day on the front page.
 */
function beforeMainContent()
{
    if (!is_user_logged_in()) {
        return;
    }

    $books  = bookOfTheDay();

    if (!$books) {
        return;
    }

    extract($books);
    if (!$title || !$image || !$url) {
        return;
    }

    wp_enqueue_style('tsjippy_library_frontpage', TSJIPPY\pathToUrl(PLUGINPATH . 'css/frontpage.min.css'), array(), PLUGINVERSION);

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
}
