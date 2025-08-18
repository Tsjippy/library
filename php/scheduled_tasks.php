<?php
namespace SIM\LIBRARY;
use SIM;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'send_book_of_the_day', __NAMESPACE__.'\sendBookOfTheDay' );
}

function scheduleTasks(){
    SIM\scheduleTask('send_book_of_the_day', 'quarterly');
}

function sendBookOfTheDay(){
    $time = SIM\getModuleOption(MODULE_SLUG, 'book-time');

    if(!$time){
        return;
    }
    
    if(abs(strtotime($time) - current_time('U')) < 450 ){        
        do_action('sim-library-send-book-of-the-day', ...bookOfTheDay());
    }
}