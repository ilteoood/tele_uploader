<?php

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;

require_once 'BotManager.php';
require_once 'Utils.php';

$dropbox = new Dropbox(new DropboxApp(getenv("DB_ID"), getenv("DB_SECRET"), getenv("DB_TOKEN")));

function handleDropboxMessage($update, $conversations)
{
    global $dropbox;
    $destination = retrieveDestination($update);
    $MadelineProto = getBotInstance();
    if (isset($conversations[$destination])) {
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
        $dropbox->upload(new DropboxFile($conversations[$destination]['downloadDir']), DIRECTORY_SEPARATOR . $conversations[$destination]['fileName'], ['autorename' => true]);
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploaded!', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
    } else
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'You need to send a file first', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
}