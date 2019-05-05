#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use danog\MadelineProto\Exception;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;

require_once 'Constants.php';
require_once 'BotManager.php';
require_once 'Utils.php';

$dropbox = new Dropbox(new DropboxApp(getenv("DB_ID"), getenv("DB_SECRET"), getenv("DB_TOKEN")));

if (!file_exists(TMP_DOWNLOADS))
    mkdir(TMP_DOWNLOADS);
$MadelineProto = makeLogin();
$offset = 0;
$conversations = array();
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }
                try {
                    $destination = retrieveDestination($update);
                    if (isset($update['update']['message']['media']) && (retrieveFromMessage($update, 'media')['_'] == 'messageMediaPhoto' || retrieveFromMessage($update, 'media')['_'] == 'messageMediaDocument')) {
                        $time = time();
                        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                        $file = $MadelineProto->download_to_file($update['update']['message']['media'], TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $update['update']['message']['media']['document']['attributes'][0]['file_name']);
                        $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloaded in ' . (time() - $time) . ' seconds', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                        $conversations[$destination] = array('downloadDir' => $file, 'fileName' => getFileName($file, DIRECTORY_SEPARATOR));
                    } else if (isset($update['update']['message']['message'])) {
                        $message = retrieveFromMessage($update, 'message');
                        if (startsWith($message, 'http://') || startsWith($message, 'https://') || startsWith($message, 'ftp://')) {
                            $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Downloading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                            try {
                                $conversations[$destination] = downloadFile($message);
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'File downloaded!', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                            } catch (Exception $e) {
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Unable to download file', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                            }
                        } else if ($message == '/dropbox') {
                            if (isset($conversations[$destination])) {
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                                $dropbox->upload(new DropboxFile($conversations[$destination]['downloadDir']), DIRECTORY_SEPARATOR . $conversations[$destination]['fileName'], ['autorename' => true]);
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploaded!', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                            } else
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'You need to send a file first', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                        } else if ($message == '/telegram') {
                            if (isset($conversations[$destination])) {
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'Uploading file...', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                                $file = ['_' => 'inputMediaUploadedDocument', 'file' => $MadelineProto->upload($conversations[$destination]['downloadDir']), 'mime_type' => 'magic/magic', 'caption' => '', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $conversations[$destination]['fileName']]]];
                                $MadelineProto->messages->sendMedia(['peer' => $destination, 'media' => $file, 'reply_to_msg_id' => retrieveFromMessage($update, 'id'), 'message' => '']);
                            } else
                                $MadelineProto->messages->sendMessage(['peer' => $destination, 'message' => 'You need to send a file first', 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
                        }
                    }
                } catch (RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@ilteoood', 'message' => $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
                }
        }
    }
}
