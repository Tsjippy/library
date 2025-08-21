<?php
namespace SIM\LIBRARY;
use SIM;
use Gemini\Data\Content;
use Gemini\Enums\Role;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addGeminiResponse', 10, 6);
function addGeminiResponse($response, $message, $source, $users, $name, $signal){
    if($response == 'I have no clue, do you know?'){
        $library = getLibrary();

        // Get message history of last hour
        $received   = $signal->getReceivedMessageLog(100, 1, time() - MINUTE_IN_SECONDS*100000, '', $users[0]->phone);

        $sent       = $signal->getSentMessageLog(100, 1, time() - MINUTE_IN_SECONDS*100000, '', $users[0]->phone);

        $messages   = array_merge($received, $sent);

        usort($messages, function($a, $b) {
            if ($a->timesend == $b->timesend) {
                return 0;
            }

            return ($a->timesend < $b->timesend) ? -1 : 1;
        });

        $history    = [];

        foreach($messages as $msg){
            if($msg->sender == null){
                $role   = Role::MODEL;
            }else{
                $role   = Role::USER;
            }

            $history[]  = Content::parse(part: $msg->message, role: $role);
        }

        return $library->chatGemini($message, $history);
    }

    return $response;
}