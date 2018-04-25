#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;

const BOT_SESSION = 'teleupload.madeline';
const TMP_DOWNLOADS = '.' . DIRECTORY_SEPARATOR . 'tmp_downloads';
$dropbox = new Dropbox(new DropboxApp(getenv("DB_ID"), getenv("DB_SECRET"), getenv("DB_TOKEN")));
set_include_path(get_include_path() . ':' . realpath(dirname(__FILE__) . '/MadelineProto/'));

$settings = ['app_info' => ['api_id' => 246968, 'api_hash' => 'dd9b27c65c119f3b82ac036859e77b53']];

try {
    $MadelineProto = new API(BOT_SESSION, $settings);
} catch (Exception $e) {
    $MadelineProto = new API($settings);
}

$authorization = $MadelineProto->bot_login(getenv("BOT_TOKEN"));

if (!file_exists(TMP_DOWNLOADS))
    mkdir(TMP_DOWNLOADS);
$MadelineProto->session = BOT_SESSION;
$offset = 0;
$conversations = array();
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    Logger::log([$updates]);
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
                                $MadelineProto->messages->sendMedia(['peer' => $destination, 'media' => $file, 'reply_to_msg_id' => retrieveFromMessage($update, 'id')]);
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

function getFileName($filePath, $separator)
{
    $splitted = explode($separator, $filePath);
    return $splitted[count($splitted) - 1];
}

function downloadFile($message)
{
    $fileName = getFileName($message, '/');
    $downloadDir = TMP_DOWNLOADS . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($downloadDir))
        file_put_contents($downloadDir, fopen("$message", 'r'));
    return array('downloadDir' => $downloadDir, 'fileName' => $fileName);
}

function startsWith($string, $toCheck)
{
    return substr($string, 0, strlen($toCheck)) === $toCheck;
}

function retrieveFromMessage($update, $toRetrieve)
{
    return $update['update']['message'][$toRetrieve];
}

function retrieveDestination($update)
{
    return isset($update['update']['message']['from_id']) ? retrieveFromMessage($update, 'from_id') : retrieveFromMessage($update, 'to_id');
}

function sendMessage($to, $message, $replyTo)
{
    global $MadelineProto;
    $MadelineProto->messages->sendMessage(['peer' => $to, 'message' => $message, 'reply_to_msg_id' => $replyTo]);
}
