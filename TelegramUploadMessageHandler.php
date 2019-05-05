<?php

require_once 'BotManager.php';
require_once 'Utils.php';

function handleTelegramMessage($update, &$conversations)
{
    $MadelineProto = getBotInstance();
    $destination = retrieveDestination($update);
    $replyMessageId = retrieveFromMessage($update, 'id');
    if (isset($conversations[$destination])) {
        sendMessage($update, 'Uploading file...');
        $file = ['_' => 'inputMediaUploadedDocument', 'file' => $MadelineProto->upload($conversations[$destination]['downloadDir']), 'mime_type' => 'magic/magic', 'caption' => '', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $conversations[$destination]['fileName']]]];
        $MadelineProto->messages->sendMedia(['peer' => $destination, 'media' => $file, 'reply_to_msg_id' => $replyMessageId, 'message' => '']);
    } else
        sendMessage($update, 'You need to send a file first');
}