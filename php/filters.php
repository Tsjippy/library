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
    return str_replace('books', 'library', $templateFile);
}