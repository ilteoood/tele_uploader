<?php

use danog\MadelineProto\Exception;

require_once 'Utils.php';

function handleDownloadMessage($update, &$conversations)
{
    $destination = retrieveDestination($update);
    $message = retrieveFromMessage($update, 'message');
    sendMessage($destination, 'Downloading file...');
    try {
        $conversations[$destination] = downloadFile($message);
        sendMessage($update, 'File downloaded!');
    } catch (Exception $e) {
        sendMessage($update, 'Unable to download file');
    }
}

function downloadFile($message)
{
    $fileName = getFileName($message, null);
    $downloadDir = TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($downloadDir))
        file_put_contents($downloadDir, fopen("$message", 'r'));
    createDownloadFileObject($downloadDir, $fileName);
}

function createDownloadFileObject($downloadDir, $fileName)
{
    return array('downloadDir' => $downloadDir, 'fileName' => $fileName);
}