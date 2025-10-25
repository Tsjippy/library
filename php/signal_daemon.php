<?php
namespace SIM\LIBRARY;
use SIM;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Exceptions\ErrorException;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addGeminiResponse', 99, 6);
function addGeminiResponse($response, $message, $source, $users, $name, $signal){
    if($response['message'] != 'I have no clue, do you know?'){
        return $response;
    }

    try {
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

        $response['message']    = "I am not sure what to answer so I asked Gemini.\n\nHere is what it said:\n".$library->chatGemini($message, $history);
    } catch ( \Gemini\Exceptions\ErrorException $e){
        SIM\printArray($e->getMessage());
    } catch (\Exception $e) {
        // Code to handle any other general Exception
        SIM\printArray("Caught a general exception: " . $e->getMessage());
    }

    return $response;
}