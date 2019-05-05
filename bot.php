#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

require_once 'Constants.php';
require_once 'BotManager.php';
require_once 'Utils.php';
require_once 'MediaMessageHandler.php';
require_once 'DownloadMessageHandler.php';
require_once 'DropboxUploadMessageHandler.php';
require_once 'TelegramUploadMessageHandler.php';
require_once 'UploadFromLocalDirectoryHandler.php';

if (!file_exists(TMP_DOWNLOADS))
    mkdir(TMP_DOWNLOADS);
$MadelineProto = makeLogin();
$offset = 0;
$conversations = array();

while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        manageSingleUpdate($update);
    }
}

function manageSingleUpdate($update)
{
    switch ($update['update']['_']) {
        case 'updateNewMessage':
        case 'updateNewChannelMessage':
            if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                continue;
            }
            try {
                if (isMediaIncoming($update)) {
                    global $conversations;
                    handleMediaMessage($update, $conversations);
                } else if (isTextMessage($update)) {
                    manageSingleTextMessage($update);
                }
            } catch (RPCErrorException $e) {
                sendMessageBase('@ilteoood', $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), null);
            }
    }
    return $update;
}

function manageSingleTextMessage($update)
{
    global $conversations;
    $message = retrieveFromMessage($update, 'message');
    if (isDownloadableFile($message)) {
        handleDownloadMessage($update, $conversations);
    } else if ($message == '/dropbox') {
        handleDropboxMessage($update, $conversations);
    } else if ($message == '/telegram') {
        handleTelegramMessage($update, $conversations);
    } else if ($message == '/upload') {
        directoryNavigationInitializationHandler($update, $conversations);
    } else {
        $destination = retrieveDestination($update);
        if (isset($conversations[$destination]) && $conversations[$destination]->isNavigation) {
            navigateDirectory($update, $conversations);
        }
    }
}