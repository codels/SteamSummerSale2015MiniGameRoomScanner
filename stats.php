<?php
date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');
$roomId = 44888;
$gameInfo = json_decode(file_get_contents('http://steamapi-a.akamaihd.net/ITowerAttackMiniGameService/GetPlayerNames/v0001/?input_json=%7B%22gameid%22%3A%22'.$roomId.'%22%2C%22accountids%22%3A%5B%5D%7D'));

$db = new PDO('mysql:host=127.0.0.1;dbname=stats', 'root', 'hi');
$db->query('SET NAMES utf8');

$statement=$db->prepare('REPLACE INTO `account_room` (`accId`, `roomId`) VALUES (?, ?)');

foreach ($gameInfo->response->names as $info) {
    $statement->execute(array($info->accountid, $roomId, $info->name));
}

foreach ($accIds as $accId) {
    $statement->execute(array($accId, $roomId));
}

echo 'ok';