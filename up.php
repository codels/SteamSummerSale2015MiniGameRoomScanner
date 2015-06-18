<?php

require_once 'config.php';

$db = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
$db->query('SET NAMES utf8');

$db->query("ALTER TABLE `rooms`
ADD COLUMN `players`  int NOT NULL DEFAULT '-1' AFTER `status`;");

echo 'ok';