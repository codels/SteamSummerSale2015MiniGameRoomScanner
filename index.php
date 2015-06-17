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
$result = json_decode(file_get_contents('http://steamga.me/data/api/all.json'));

$endDateTime = new DateTime();
$endDateTime->setTime(19, 00, 00);

$currentDateTime = new DateTime();

if ($endDateTime < $currentDateTime) {
    $interval = new DateInterval('P1D');
    $endDateTime->add($interval);
}

$endTimeStamp = $endDateTime->getTimestamp();

$array = array();
foreach ($result as $res) {
    $res->lvl_per_wh = $res->wormholes > 0 ? $res->level / $res->wormholes : 0;
    $res->level_per_seconds = $res->level / (time() - $res->timestamp_game_start);
    $res->possible_max_level = $res->level_per_seconds * ($endTimeStamp - $res->timestamp_game_start);
    $array[] = $res;
}

// render
echo "<table  id='tablesorter-demo' class='tablesorter' border='0' cellpadding='0' cellspacing='1'>
<thead>
<tr>
    <th>Room ID</th>
    <th>Level per seconds</th>
    <th>Level per wormholes</th>
    <th>Possible max level</th>
</tr>
</thead><tbody>";
foreach ($array as $res) {
    echo '<tr>';

    echo "<td>{$res->id}</td><td>{$res->level_per_seconds}</td><td>{$res->lvl_per_wh}</td><td>{$res->possible_max_level}</td>";

//var_dump($res);
    echo '</tr>';
}

echo '</tbody></table>';

?>

</body>
</html>