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
            <input type='time' name="book-time" value='<?php echo $this->settings['book-time'] ?? ''; ?>'>
        </label>
        <?php

        TSJIPPY\addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function emails($parent)
    {

        return false;
    }

    public function data($parent = '')
    {

        return false;
    }

    public function functions($parent)
    {
        $library        = new Library();

        ob_start();

        if (!empty($_FILES['books'])) {
            echo $library->processImage($_FILES['books']['tmp_name']);
        } else {
            echo $library->getFileHtml();
        }

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
            <h4>Sync Books with OpenLibrary.org</h4>
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
     */
    public function postActions($request)
    {
        $library        = new Library();

        foreach (TSJIPPY\sanitize($request['books'] ?? []) as $book) {
            $library->createBook($book);
        }
    }
}
