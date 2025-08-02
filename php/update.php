<?php
namespace SIM\LIBRARY;
use SIM;

add_action('sim_library_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $library = new Library();

    if($oldVersion < '2.0.1'){
        maybe_add_column($simForms->tableName, 'reminder_amount', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_amount` LONGTEXT");

        SIM\printArray("Added column");
    }
}