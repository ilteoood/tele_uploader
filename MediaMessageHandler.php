<?php

require_once 'BotManager.php';
require_once 'Utils.php';

function handleMediaMessage($update, &$conversations)
{
    $MadelineProto = getBotInstance();
    $destination = retrieveDestination($update);
    $currentTime = time();
    sendMessage($update, 'Downloading file...');
    $file = $MadelineProto->download_to_file($update['update']['message']['media'], TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $update['update']['message']['media']['document']['attributes'][0]['file_name']);
    sendMessage($update, 'Downloaded in ' . (time() - $currentTime) . ' seconds');
    $conversations[$destination] = array('downloadDir' => $file, 'fileName' => getFileName($file, DIRECTORY_SEPARATOR));
}