<?php

require_once 'Utils.php';
require_once 'BotManager.php';
require_once 'DownloadMessageHandler.php';

function directoryNavigationInitializationHandler($update, &$conversations)
{
    $destination = retrieveDestination($update);
    $conversations[$destination] = new stdClass();
    $conversations[$destination]->isNavigation = true;
    $conversations[$destination]->currentPath = getenv('NAVIGATION_ROOT');
    createAndSendDirectoryContent($update, getenv('NAVIGATION_ROOT'));
}

function navigateDirectory($update, &$conversations)
{
    $destination = retrieveDestination($update);
    $message = retrieveFromMessage($update, 'message');
    $newPath = $conversations[$destination]->currentPath . DIRECTORY_SEPARATOR . $message;
    if (is_file($newPath)) {
        $conversations[$destination] = createDownloadFileObject($newPath, getFileName($newPath, DIRECTORY_SEPARATOR));
        sendMessage($update, 'Ok, I\'ll consider this file');
    } else {
        $conversations[$destination]->currentPath = $newPath;
        createAndSendDirectoryContent($update, $newPath);
    }
}

function createAndSendDirectoryContent($update, $directoryPath)
{
    $MadelineProto = getBotInstance();
    $replyMessageId = retrieveFromMessage($update, 'id');
    $keyboardMarkup = createMarkupContent($directoryPath);
    $MadelineProto->messages->sendMessage(['peer' => retrieveDestination($update),
        'message' => 'Here is the directory list',
        'reply_markup' => $keyboardMarkup, 'reply_to_msg_id' => $replyMessageId]);
}

function createMarkupContent($directoryPath)
{
    $directories = [];
    foreach (scandir($directoryPath) as $directory) {
        $button = ['_' => 'keyboardButton', 'text' => $directory];
        array_push($directories, ['_' => 'keyboardButtonRow', 'buttons' => [$button]]);
    }
    return ['_' => 'replyKeyboardMarkup', 'resize' => false, 'single_use' => true, 'selective' => false,
        'rows' => $directories];
}