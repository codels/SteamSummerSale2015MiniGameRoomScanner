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

if (empty($_REQUEST['room_id'])) {
    return;
}

$roomId = intval($_REQUEST['room_id']);

require_once 'config.php';

$db = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
$db->query('SET NAMES utf8');

$statementSearch = $db->prepare('SELECT `status`, `level` FROM `rooms` WHERE `id` = ?');
$statementSearch->execute(array($roomId));
$info = $statementSearch->fetch(PDO::FETCH_ASSOC);
if (!empty($info['status']) && $info['status'] == 3) {
    $level = $info['level'];
    $status = $info['status'];
    $players = -1;
    $activePlayers = -1;
} else {
    $statementUpdate = $db->prepare('REPLACE INTO `rooms` (`id`, `level`, `status`, `players`) VALUES (?, ?, ?, ?)');

    $level = -1;
    $status = -1;
    $players = -1;
    $activePlayers = -1;

    $gameInfo = json_decode(file_get_contents('http://steamapi-a.akamaihd.net/ITowerAttackMiniGameService/GetGameData/v0001/?gameid='.$roomId.'&include_stats=1'));

    if (property_exists($gameInfo, 'response')) {
        if (property_exists($gameInfo->response, 'game_data')) {
            if (property_exists($gameInfo->response->game_data, 'level')) {
                $level = $gameInfo->response->game_data->level;
            }
            if (property_exists($gameInfo->response->game_data, 'status')) {
                $status = $gameInfo->response->game_data->status;
            }
        }
        if (property_exists($gameInfo->response, 'stats')) {
            if (property_exists($gameInfo->response->stats, 'num_players')) {
                $players = $gameInfo->response->stats->num_players;
            }
            if (property_exists($gameInfo->response->stats, 'num_active_players')) {
                $activePlayers = $gameInfo->response->stats->num_active_players;
            }
        }
    }

    $statementUpdate->execute(array($roomId, $level, $status, $players));
}

echo json_encode(array(
    'room_id' => $roomId,
    'level' => $level,
    'status' => $status,
    'players' => $players,
    'activePlayers' => $activePlayers
), JSON_FORCE_OBJECT);