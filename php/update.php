<?php
namespace SIM\LIBRARY;
use SIM;

use function Crontrol\Event\delete;

add_action('sim_library_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $library = new Library();

    if($oldVersion < '1.0.6'){
        global $wpdb;
        $query  = "SELECT pm.meta_value, p.ID
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = 'author'
            AND p.post_type = 'book'";

        $results = $wpdb->get_results($query);

        foreach($results as $result){
            $library->processAuthors($result->meta_value, $result->ID);

            delete_post_meta($result->ID, 'author');
        }

        $query  = "SELECT pm.meta_value, p.ID
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = 'locations'
            AND p.post_type = 'book'";

        $results = $wpdb->get_results($query);

        foreach($results as $result){
            wp_set_post_terms($result->ID, $result->meta_value, 'book-locations', true);

            add_post_meta($result->ID, 'location', $result->meta_value);
            delete_post_meta($result->ID, 'locations');
        }
    }
}