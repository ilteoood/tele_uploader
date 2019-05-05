<?php

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;

require_once 'Utils.php';

$dropbox = new Dropbox(new DropboxApp(getenv("DB_ID"), getenv("DB_SECRET"), getenv("DB_TOKEN")));

function handleDropboxMessage($update, &$conversations)
{
    global $dropbox;
    $destination = retrieveDestination($update);
    if (isset($conversations[$destination])) {
        sendMessage($update, 'Uploading file...');
        $dropbox->upload(new DropboxFile($conversations[$destination]['downloadDir']), DIRECTORY_SEPARATOR . $conversations[$destination]['fileName'], ['autorename' => true]);
        sendMessage($update, 'Uploaded!');
    } else
        sendMessage($update, 'You need to send a file first');
}