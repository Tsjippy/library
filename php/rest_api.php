<?php
namespace TSJIPPY\LIBRARY;
use TSJIPPY;
use WP_Error;

if ( ! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', __NAMESPACE__ . '\restApiInit');
function restApiInit() {

    //add_book
    register_rest_route(
        RESTAPIPREFIX. '/library',
        '/add_book',
        array(
            'methods'                 => 'POST',
            'callback'             => __NAMESPACE__ . '\addBook',
            'permission_callback'     => function () {
                return current_user_can('read');        // Allow access to logged in users
            },
            'args'                    => array(
                'title'        => array(
                    'required'    => true
               ),
                'description'    => array(
                    'required'    => true
               ),
                'author'    => array(
                    'required'    => true
               )
           )
       )
   );
}

function addBook() {

    $library    = new Library();

    $result        = $library->createBook($_POST);

    return $result;
}