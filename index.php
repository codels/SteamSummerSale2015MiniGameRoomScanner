<html>
<head>
    <link rel="stylesheet" href="http://tablesorter.com/themes/blue/style.css" type="text/css"
          media="print, projection, screen"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="http://tablesorter.com/__jquery.tablesorter.min.js"></script>
    <script type="text/javascript">
        $(function () {
            $("#tablesorter-demo").tablesorter({widgets: ['zebra']});
            //$("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
        });
    </script>
</head>
<body>
<?php
date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');
$objectsAllRooms = json_decode(file_get_contents('http://steamga.me/data/api/all.json'));
$objectsTopRooms = json_decode(file_get_contents('http://steamga.me/data/api/leaderboard.json'));
$roomsName = json_decode(file_get_contents('./rooms_name.json'));

$endDateTime = new DateTime();
$endDateTime->setTime(19, 00, 00);

$currentDateTime = new DateTime();

if ($endDateTime < $currentDateTime) {
    $interval = new DateInterval('P1D');
    $endDateTime->add($interval);
}

$endTimeStamp = $endDateTime->getTimestamp();

$maxPlayers = 1500;

$array = array();
foreach ($objectsAllRooms as $objectRoom) {
    $objectRoom->lvl_per_wh = $objectRoom->wormholes > 0 ? $objectRoom->level / $objectRoom->wormholes : 0;
    $objectRoom->level_per_seconds = $objectRoom->level / (time() - $objectRoom->timestamp_game_start);
    $objectRoom->possible_max_level = $objectRoom->level_per_seconds * ($endTimeStamp - $objectRoom->timestamp_game_start);
    $objectRoom->name = 'unknown';

    $nameFound = false;

    foreach ($objectsTopRooms as $objectTopRoom) {
        if ($objectTopRoom->id == $objectRoom->id) {
            if ($objectTopRoom->name != 'unknown') {
                $objectRoom->name = $objectTopRoom->name;
                $nameFound = true;
            }
            break;
        }
    }

    if (!$nameFound) {
        foreach ($roomsName as $room) {
            if ($room->id == $objectRoom->id) {
                $objectRoom->name = $room->name;
                $nameFound = true;
                break;
            }
        }
    }

    $array[] = $objectRoom;
}

// render
echo "<table  id='tablesorter-demo' class='tablesorter' border='0' cellpadding='0' cellspacing='1'>
<thead>
<tr>
    <th>#</th>
    <th>Room ID</th>
    <th>Room name</th>
    <th>Players</th>
    <th>Current level</th>
    <th>Level per seconds</th>
    <th>Level per wormholes</th>
    <th>Possible max level</th>
    <th>Wormholes</th>
</tr>
</thead><tbody>";
foreach ($array as $objectRoom) {
    echo '<tr>';

    echo "
<td>{$objectRoom->position}</td>
<td>{$objectRoom->id}</td>
<td>{$objectRoom->name}</td>
<td style='" . ($objectRoom->players == 1500 ? 'color: red;' : '') . "'>{$objectRoom->players}</td>
<td>{$objectRoom->level}</td>
<td>{$objectRoom->level_per_seconds}</td>
<td>{$objectRoom->lvl_per_wh}</td>
<td>{$objectRoom->possible_max_level}</td>
<td>{$objectRoom->wormholes}</td>
";

//var_dump($res);
    echo '</tr>';
}

echo '</tbody></table>';

?>

</body>
</html>