<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    /**
     * Function to display the settings page
     *
     * @param   string  $parent The parent menu slug
     * @return  bool            True if the settings page was displayed, false otherwise
     */
    public function settings($parent)
    {
        $connectors = [];

        foreach (wp_get_connectors() as $name => $connector) {
            if ($connector['plugin']['is_active']()) {
                $connectors[$name] = $connector;
            }
        }

        ob_start();

        if (empty($connectors)) {
?>
            <div class='warning'>
                You have no active AI connectors add one <a href='<?php echo esc_url(admin_url('options-connectors.php')); ?>' target='_blank'>here</a>.
            </div>
        <?php
        }

        ?>
        <br>
        <br>
        <label>
            What time should I send a book of the day message?
            <br>
            <input type='time' name="book-time" value='<?php echo esc_attr($this->settings['book-time'] ?? ''); ?>'>
        </label>
        <?php

        TSJIPPY\addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Function to display the emails page
     *
     * @param   string  $parent The parent menu slug
     * @return  bool            True if the emails page was displayed, false otherwise
     */
    public function emails($parent)
    {

        return false;
    }

    /**
     * Function to display the data page
     *
     * @param   string  $parent The parent menu slug
     * @return  bool            True if the data page was displayed, false otherwise
     */
    public function data($parent = '')
    {

        return false;
    }

    /**
     * Function to display the functions page
     *
     * @param   string  $parent The parent menu slug
     * @return  bool            True if the functions page was displayed, false otherwise
     */
    public function functions($parent)
    {
        $library        = new Library();

        ob_start();

        // phpcs:ignore
        if (!empty($_FILES['books'])) {
            $library->processImage(TSJIPPY\sanitize($_FILES['books']['tmp_name']), true);
        } else {
            $library->getFileHtml();
        }

        // phpcs:ignore
        if (!empty($_REQUEST['updatemeta'])) {
            wp_schedule_single_event(time(), 'tsjippy-library-update-metas');

        ?>
            <div class='success'>
                <p>Updating book metas in the background. This can take a while.</p>
            </div>
        <?php
        } else {
        ?>
            <br>
            <br>
            <h4>
                Sync Books with OpenLibrary.org
            </h4>
            <form method='post'>
                <input type='hidden' class='no-reset' name='updatemeta' value='updatemeta'>
                <button class='button sim small'>Update Book Metas</button>
    <?php
        }

        TSJIPPY\addRawHtml(ob_get_clean(), $parent);


        return true;
    }

    /**
     * Function to do extra actions from $request data. Overwrite if needed
     * 
     * @param   array   $request    The request data
     * @return  void
     */
    public function postActions($request)
    {
        $library        = new Library();

        foreach (TSJIPPY\sanitize($request['books'] ?? []) as $book) {
            $library->createBook($book);
        }
    }
}
