<?php
namespace TSJIPPY\LIBRARY;
use TSJIPPY;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'send_book_of_the_day', __NAMESPACE__.'\sendBookOfTheDay' );
}

function scheduleTasks(){
    TSJIPPY\scheduleTask('send_book_of_the_day', 'quarterly');
}

function sendBookOfTheDay(){
    $time = SETTINGS['book-time'] ?? false;

    if(!$time){
        return;
    }
    
    if(abs(strtotime($time) - current_time('U')) < 450 ){        
        do_action('tsjippy-library-send-book-of-the-day', ...bookOfTheDay());
    }
}