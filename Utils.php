<?php

require_once 'BotManager.php';

function getFileName($filePath, $separator)
{
    $realFileName = '';
    if (isset($separator)) {
        $splitted = explode($separator, $filePath);
        $realFileName = $splitted[count($splitted) - 1];
    } else {
        $content = get_headers($filePath, 1);
        $content = array_change_key_case($content, CASE_LOWER);
        if ($content['content-disposition']) {
            $splitted = explode(';', $content['content-disposition']);
            if ($splitted[1]) {
                $splitted = explode('=', $splitted[1]);
                $realFileName = trim($splitted[1], '"');
            }
        } else {
            $stripped_url = preg_replace('/\\?.*/', '', $filePath);
            $realFileName = basename($stripped_url);
        }
    }
    return $realFileName;
}

function downloadFile($message)
{
    $fileName = getFileName($message, null);
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