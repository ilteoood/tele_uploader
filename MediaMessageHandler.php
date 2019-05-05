<?php

require_once 'BotManager.php';
require_once 'Utils.php';

function handleMediaMessage($update, $conversations)
{
    $MadelineProto = getBotInstance();
    $destination = retrieveDestination($update);
    $currentTime = time();
    $replyMessageId = retrieveFromMessage($update, 'id');
    sendMessage($destination, 'Downloading file...', $replyMessageId);
    $file = $MadelineProto->download_to_file($update['update']['message']['media'], TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $update['update']['message']['media']['document']['attributes'][0]['file_name']);
    sendMessage($destination, 'Downloaded in ' . (time() - $currentTime) . ' seconds', $replyMessageId);
    $conversations[$destination] = array('downloadDir' => $file, 'fileName' => getFileName($file, DIRECTORY_SEPARATOR));
}