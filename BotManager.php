<?php

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;

set_include_path(get_include_path() . ':' . realpath(dirname(__FILE__) . '/MadelineProto/'));

$MadelineProto = null;
$authorization = null;

function makeLogin()
{
    global $MadelineProto, $authorization;
    try {
        $MadelineProto = new API(BOT_SESSION, SETTINGS);
    } catch (Exception $e) {
        $MadelineProto = new API(SETTINGS);
    }
    $authorization = $MadelineProto->bot_login(getenv("BOT_TOKEN"));
    $MadelineProto->session = BOT_SESSION;
    return $MadelineProto;
}

function getBotInstance()
{
    global $MadelineProto;
    return $MadelineProto;
}