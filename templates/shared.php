<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) exit;

function addBooksModal()
{

    $library        = new Library();

?>
    <div
        id='add-books-modal'
        class='modal 
    <?php if (empty($_GET['addbooks'])) echo 'hidden'; ?>'>
        <div class="modal-content" style='max-width:100vw;'>
            <?php TSJIPPY\addCloseButtton(); ?>
            <div class="content">
                <?php $library->getFileHtml(); ?>
                <br>
                <br>
            </div>
        </div>
    </div>
    <?php
}

function displayLocationTax()
{
    wp_enqueue_style('tsjippy_taxonomy_style');

    global $wp_query;

    $skipWrapper    = false;

    if ($wp_query->is_embed) {
        $skipWrapper    = true;
    }

    if ($skipWrapper) {
        displayBooks();
    } else {
        if (!isset($skipHeader) || !$skipHeader) {
            get_header();
        }

        addBooksModal();

    ?>
        <div id="primary">
            <main id="main" class='taxonomy inside-article'>
                <button type='button' class='tsjippy button add-books' onclick='Main.showModal(`add-books`)'>Add books</button>
                <?php displayBooks(); ?>
            </main>
        </div>
        <?php
        generate_construct_sidebars();

        if (!isset($skipFooter) || !$skipFooter) {
            get_footer();
        }
    }
}

function displayBooks()
{
    $name                 = get_queried_object()->slug;
    if (have_posts()) {
        do_action('tsjippy-before-archive', 'book');

        //only show the map if logged in
        if (is_user_logged_in()) {
            $mapName            = $name . "_map";
            $mapId                = SETTINGS[$mapName] ?? false;

            if (is_numeric($mapId)) {
                //Show the map of this category
                ?>
                <div style='margin-bottom:25px;'>
                    <?php echo wp_kses_post(do_shortcode("[ultimate_maps id='$mapId']")); ?>
                </div>
                <?php
            }
        }

        while (have_posts()) :
            the_post();
            include(__DIR__ . '/content.php');
        endwhile;

        the_posts_pagination();
    } else {
        //No items with this category
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="no-results not-found">
                <div class="inside-article">
                    <div class="entry-content">
                        <?php echo wp_kses_post(apply_filters('tsjippy-empty-taxonomy', "There are no $name books yet", 'book')); ?>
                    </div>
                </div>
            </div>
        </article>
<?php
    }
}
