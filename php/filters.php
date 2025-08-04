<?php
namespace SIM\LIBRARY;
use SIM;

add_filter( 'post_thumbnail_html', __NAMESPACE__.'\setExternalImage', 999, 5 );
function setExternalImage($html, $postId, $postThumbnailId, $size, $attr ){
    return $html;
}


// Make sure the template path includes the library folder, not the books folder
add_filter('sim-template-filter', __NAMESPACE__.'\changeModuleName');
function changeModuleName($templateFile){
    return str_replace('/books/', '/library/', $templateFile);
}

add_action ( 'wp_ajax_process_library_upload', __NAMESPACE__.'\ajaxUploadFiles');
function ajaxUploadFiles(){
    global $Modules;

    if(!empty($_FILES['files'])){
        $library		= getLibrary($Modules[MODULE_SLUG]);
        $files	        = $_FILES['files'];
        $result         = '';

        foreach($files['tmp_name'] as $key => $path){
            if(is_file($path)){
                $result         .= $library->processImage($path);
            }
        }
        
        if($result){
            wp_send_json_success($result);
        }else{
            wp_send_json_error('Failed to process the image');
        }
    }else{
        wp_send_json_error('No files uploaded');
    }
}