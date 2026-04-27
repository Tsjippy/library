<?php
namespace TSJIPPY\LIBRARY;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        ob_start();
	
        ?>
            <label>
                <h4>ChatGPT Api Key</h4>
                <input type='text' name='chatgpt-api-key' value='<?php echo $this->settings['chatgpt-api-key'] ?? '';?>' style='width: -webkit-fill-available;'>
            </label>
            <br>
            <label>
                <h4>Gemini Api Key</h4>
                <input type='text' name='gemini-api-key' value='<?php echo $this->settings['gemini-api-key'] ?? '';?>' style='width: -webkit-fill-available;'>
            </label>
            <br>		
        <?php
        ?>
            <br>
            <br>
            <label>
                What time should I send a book of the day message?
                <br>
                <input type='time' name="book-time" value='<?php echo $this->settings['book-time'] ?? '';?>'>
            </label>
		<?php

        TSJIPPY\addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function emails($parent){
        ob_start();

        ?>
        <label>
            Define the e-mail people get when they need to fill in some mandatory form information.<br>
            There is one e-mail to adults, and one to parents of children with missing info.<br>
        </label>
        <br>

        <?php
        $emails    = new Email(wp_get_current_user());
        $emails->printPlaceholders();
        ?>

        <h4>E-mail to adults</h4>
        <?php

        $emails->printInputs($this->settings);

        TSJIPPY\addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){
        $library		= getLibrary();

        ob_start();

        if(!empty($_FILES['books'])){
            echo $library->processImage($_FILES['books']['tmp_name']);
        }else{
            echo $library->getFileHtml();
        }
        
        if(!empty($_REQUEST['updatemeta'])){
            wp_schedule_single_event(time(), 'tsjippy-updatemetas');

            ?>
            <div class='success'>
                <p>Updating book metas in the background. This can take a while.</p>
            </div>
            <?php	
        }else{
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
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        $library		= getLibrary();

        foreach($_POST['books'] as $book){
            $library->createBook($book);
        }
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();
    }
}