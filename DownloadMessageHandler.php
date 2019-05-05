<?php

use danog\MadelineProto\Exception;

require_once 'BotManager.php';
require_once 'Utils.php';

function handleDownloadMessage($update){
    $destination = retrieveDestination($update);
    $message = retrieveFromMessage($update, 'message');
    $MadelineProto = getBotInstance();
    $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    try {
        $conversations[$destination] = downloadFile($message);
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'File downloaded!', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    } catch (Exception $e) {
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Unable to download file', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    }
}