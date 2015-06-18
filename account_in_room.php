<?php
date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');

$data = file_get_contents('php://input');
if (!empty($data)) {
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        $parse = json_decode($data, true);
        $_REQUEST = array_merge($_REQUEST, $parse);
    }
}

if (empty($_REQUEST['room_id']) || empty($_REQUEST['account_id'])) {
    return;
}

$roomId = intval($_REQUEST['room_id']);
$accountId = $_REQUEST['account_id'];
$gameInfo = json_decode(file_get_contents('http://steamapi-a.akamaihd.net/ITowerAttackMiniGameService/GetPlayerNames/v0001/?input_json=%7B%22gameid%22%3A%22'.$roomId.'%22%2C%22accountids%22%3A%5B%5D%7D'));

$players = -1;
$exists = false;

if (property_exists($gameInfo, 'response')) {
    if (property_exists($gameInfo->response, 'names')) {
        $players = count($gameInfo->response->names);
        foreach ($gameInfo->response->names as $player) {
            if (is_array($accountId)) {
                if (in_array($player->accountid, $accountId)) {
                    $exists = true;
                }
            } else {
                if ($player->accountid == $accountId) {
                    $exists = true;
                }
            }
        }
    }
}

echo json_encode(array(
    'room_id' => $roomId,
    'players' => $players,
    'exists' => $exists
), JSON_FORCE_OBJECT);