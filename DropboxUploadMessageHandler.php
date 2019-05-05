<?php

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;

require_once 'Utils.php';

$dropbox = new Dropbox(new DropboxApp(getenv("DB_ID"), getenv("DB_SECRET"), getenv("DB_TOKEN")));

function handleDropboxMessage($update, $conversations)
{
    global $dropbox;
    $destination = retrieveDestination($update);
    $replyMessageId = retrieveFromMessage($update, 'id');
    if (isset($conversations[$destination])) {
        sendMessage($destination, 'Uploading file...', $replyMessageId);
        $dropbox->upload(new DropboxFile($conversations[$destination]['downloadDir']), DIRECTORY_SEPARATOR . $conversations[$destination]['fileName'], ['autorename' => true]);
        sendMessage($destination, 'Uploaded!', $replyMessageId);
    } else
        sendMessage($destination, 'You need to send a file first', $replyMessageId);
}