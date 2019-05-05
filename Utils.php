<?php

require_once 'BotManager.php';

function getFileName($filePath, $separator)
{
    $splitted = explode($separator, $filePath);
    return $splitted[count($splitted) - 1];
}

function downloadFile($message)
{
    $fileName = getFileName($message, '/');
    $downloadDir = TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($downloadDir))
        file_put_contents($downloadDir, fopen("$message", 'r'));
    return array('downloadDir' => $downloadDir, 'fileName' => $fileName);
}

function startsWith($string, $toCheck)
{
    return substr($string, 0, strlen($toCheck)) === $toCheck;
}

function retrieveFromMessage($update, $toRetrieve)
{
    return $update['update']['message'][$toRetrieve];
}

function retrieveDestination($update)
{
    return isset($update['update']['message']['from_id']) ? retrieveFromMessage($update, 'from_id') : retrieveFromMessage($update, 'to_id');
}

function sendMessage($to, $message, $replyTo)
{
    $MadelineProto = getBotInstance();
    $MadelineProto->messages->sendMessage(['peer' => $to, 'message' => $message, 'reply_to_msg_id' => $replyTo]);
}

function isMediaIncoming($update)
{
    return isset($update['update']['message']['media']) && (retrieveFromMessage($update, 'media')['_'] == 'messageMediaPhoto'
            || retrieveFromMessage($update, 'media')['_'] == 'messageMediaDocument');
}

function isTextMessage($update)
{
    return isset($update['update']['message']['message']);
}

function isDownloadableFile($message)
{
    return startsWith($message, 'http://') || startsWith($message, 'https://') || startsWith($message, 'ftp://');
}