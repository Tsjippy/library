<?php
namespace SIM\LIBRARY;
use SIM;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'send_book_of_the_day', __NAMESPACE__.'\bookOfTheDay' );
}

function scheduleTasks(){
    
    $freq   = SIM\getModuleOption(MODULE_SLUG, 'book_of_the_day_freq');
    if($freq){
        SIM\scheduleTask('send_book_of_the_day', $freq);
    }
}

$book = bookOfTheDay();