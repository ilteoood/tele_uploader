<?php

require_once 'BotManager.php';
require_once 'Utils.php';

function handleTelegramMessage($update, $conversations)
{
    $MadelineProto = getBotInstance();
    $destination = retrieveDestination($update);
    if (isset($conversations[$destination])) {
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
        $file = ['_' => 'inputMediaUploadedDocument', 'file' => $MadelineProto->upload($conversations[$destination]['downloadDir']), 'mime_type' => 'magic/magic', 'caption' => '', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $conversations[$destination]['fileName']]]];
        $MadelineProto->messages->sendMedia(['peer' => $destination, 'media' => $file, 'reply_to_msg_id' => retrieveFromMessage($update, 'id'), 'message' => '']);
    } else
        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'You need to send a file first', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
}