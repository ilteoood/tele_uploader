<?php

use danog\MadelineProto\Exception;

require_once 'Utils.php';

function handleDownloadMessage($update)
{
    $destination = retrieveDestination($update);
    $message = retrieveFromMessage($update, 'message');
    $replyMessageId = retrieveFromMessage($update, 'id');
    sendMessage($destination, 'Downloading file...', $replyMessageId);
    try {
        $conversations[$destination] = downloadFile($message);
        sendMessage($destination, 'File downloaded!', $replyMessageId);
    } catch (Exception $e) {
        sendMessage($destination, 'Unable to download file', $replyMessageId);
    }
}