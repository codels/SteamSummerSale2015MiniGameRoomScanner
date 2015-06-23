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

$players = -1;
$level = -1;
$status = -1;

$context = stream_context_create(array('http' =>
    array(
        'timeout' => 3,
    )
));

$response = @file_get_contents('http://steamapi-a.akamaihd.net/ITowerAttackMiniGameService/GetGameData/v0001/?gameid='.$roomId.'&include_stats=1', null, $context);

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
}

echo json_encode(array(
    'room_id' => $roomId,
    'level' => $level,
    'status' => $status,
    'players' => $players
), JSON_FORCE_OBJECT);