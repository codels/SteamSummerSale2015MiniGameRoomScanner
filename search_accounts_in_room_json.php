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

$context = stream_context_create(array('http' =>
    array(
        'timeout' => 5,
    )
));

$response = @file_get_contents('http://steamapi-a.akamaihd.net/ITowerAttackMiniGameService/GetPlayerNames/v0001/?input_json=%7B%22gameid%22%3A%22' . $roomId . '%22%2C%22accountids%22%3A%5B%5D%7D', null, $context);

if ($response === false) {
    return;
}

$gameInfo = json_decode($response);

if ($gameInfo === false) {
    return;
}

if (!property_exists($gameInfo, 'response')) {
    return;
}

if (!property_exists($gameInfo->response, 'names')) {
    return;
}

$players = count($gameInfo->response->names);

require_once 'config.php';

$db = new PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName, $dbUser, $dbPass);
$db->query('SET NAMES utf8');

$statementUp = $db->prepare('UPDATE `rooms` SET `players` = ? WHERE `id` = ?');
$statementUp->execute(array($players, $roomId));

$exists = false;
$accountsFound = array();

foreach ($gameInfo->response->names as $player) {
    if (is_array($accountId)) {
        if (in_array($player->accountid, $accountId)) {
            $exists = true;
            $accountsFound[] = $player->accountid;
        }
    } else {
        if ($player->accountid == $accountId) {
            $exists = true;
            $accountsFound[] = $player->accountid;
        }
    }
}

echo json_encode(array(
    'room_id' => $roomId,
    'players' => $players,
    'exists' => $exists,
    'accounts_found' => $accountsFound
), JSON_FORCE_OBJECT);
