<?php

namespace TSJIPPY\LIBRARY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\scheduleTasks');
/**
 * Schedule all tasks for this plugin
 */
function scheduleTasks()
{
    TSJIPPY\scheduleTask('tsjippy-library-send-book-of-the-day-task', 'quarterly', __NAMESPACE__, 'sendBookOfTheDay');
}

function sendBookOfTheDay()
{
    $time = SETTINGS['book-time'] ?? false;

    if (!$time) {
        return;
    }

    if (abs(strtotime($time) - current_time('U')) < 450) {
        do_action('tsjippy-library-send-book-of-the-day', ...bookOfTheDay());
    }
}
