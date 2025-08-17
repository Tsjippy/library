<?php
namespace SIM\LIBRARY;
use SIM;

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'send_book_of_the_day', __NAMESPACE__.'\bookOfTheDay' );
}

function scheduleTasks(){
    SIM\scheduleTask('send_book_of_the_day', 'quarterly');
}

function sendBookOfTheDay(){
    $time = SIM\getModuleOption(MODULE_SLUG, 'book-time');
    
    if($time == time()){
        $book = bookOfTheDay();
        
        do_action('sim-library-send-book-of-the-day', $book);
    }
}