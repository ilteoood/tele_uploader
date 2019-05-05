<?php

require_once 'BotManager.php';
require_once 'Utils.php';

function handleMediaMessage($update, $conversations){
    $MadelineProto = getBotInstance();
    $destination = retrieveDestination($update);
    $currentTime = time();
    $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    $file = $MadelineProto->download_to_file($update['update']['message']['media'], TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $update['update']['message']['media']['document']['attributes'][0]['file_name']);
    $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloaded in ' . (time() - $currentTime) . ' seconds', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    $conversations[$destination] = array('downloadDir' => $file, 'fileName' => getFileName($file, DIRECTORY_SEPARATOR));
}