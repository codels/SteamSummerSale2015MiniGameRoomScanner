<html>
<head>
    <meta charset='utf-8'>
    <link rel="stylesheet" href="http://tablesorter.com/themes/blue/style.css" type="text/css"
          media="print, projection, screen"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="http://tablesorter.com/__jquery.tablesorter.min.js"></script>
    <script type='text/javascript' src="http://documentcloud.github.com/underscore/underscore-min.js"></script>

    <style>
        table.tablesorter tr.even:hover td,
        table.tablesorter tr.odd:hover td {
            background-color: #55c7bf;
        }

        table.tablesorter tr.me-room td {
            font-weight: bold !important;
            background-color: #d7b3f0 !important;
        }
    </style>
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
    $objectRoom->lvl_per_wh = round($objectRoom->wormholes > 0 ? $objectRoom->level / $objectRoom->wormholes : 0, 2);
    $objectRoom->level_per_seconds = round($objectRoom->level / (time() - $objectRoom->timestamp_game_start), 2);
    $objectRoom->possible_max_level = round($objectRoom->level_per_seconds * ($endTimeStamp - $objectRoom->timestamp_game_start));
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

    // format
    $objectRoom->possible_max_level_format = number_format($objectRoom->possible_max_level, 0, '.', ' ');
    $objectRoom->clicks_format = number_format($objectRoom->clicks, 0, '.', ' ');
    $objectRoom->abilities_format = number_format($objectRoom->abilities, 0, '.', ' ');
    $objectRoom->level_format = number_format($objectRoom->level, 0, '.', ' ');
    $objectRoom->wormholes_format = number_format($objectRoom->wormholes, 0, '.', ' ');

    $array[] = $objectRoom;
}

$i = 0;


// render
echo "<table  id='tablesorter-demo' class='tablesorter' border='0' cellpadding='0' cellspacing='1'>
<thead>
<tr>
    <th>#</th>
    <th>Position</th>
    <th>Room ID</th>
    <th>Room name</th>
    <th>Players</th>
    <th>Active</th>
    <th>Clicks</th>
    <th>Abilities</th>
    <th>Current level</th>
    <th>Level per seconds</th>
    <th>Level per wormholes</th>
    <th>Possible max level</th>
    <th>Wormholes</th>
    <th>ok</th>
</tr>
</thead><tbody>";
foreach ($array as $objectRoom) {
    echo "<tr class='" . ('') . "' id='{$objectRoom->id}'>";

    echo "
<td>" . (++$i) . "</td>
<td>{$objectRoom->position}</td>
<td>{$objectRoom->id}</td>
<td>{$objectRoom->name}</td>
<td style='" . ($objectRoom->players == 1500 ? 'color: red;' : '') . "'>{$objectRoom->players}</td>
<td>{$objectRoom->active_players}</td>
<td data-value={$objectRoom->clicks}>{$objectRoom->clicks_format}</td>
<td data-value={$objectRoom->abilities}>{$objectRoom->abilities_format}</td>
<td data-value={$objectRoom->level}>{$objectRoom->level_format}</td>
<td>{$objectRoom->level_per_seconds}</td>
<td>{$objectRoom->lvl_per_wh}</td>
<td data-value={$objectRoom->possible_max_level}>{$objectRoom->possible_max_level_format}</td>
<td data-value={$objectRoom->wormholes}>{$objectRoom->wormholes_format}</td>
<td><a href='javascript::void(0);' onclick='func_ok({$objectRoom->id})'>ok</a></td>
";

//var_dump($res);
    echo '</tr>';
}

echo '</tbody></table>';

?>

<script type="text/javascript">
    // target the number column using a zero-based index
    var number_column = 0;

    // add custom numbering widget
    $.tablesorter.addWidget({
        id: "numbering",
        format: function (table) {
            var c = table.config;
            $("tr:visible", table.tBodies[0]).each(function (i) {
                $(this).find('td').eq(number_column).text(i + 1);
            });
        }
    });

    $(function () {
        $("#tablesorter-demo").tablesorter({
            widgets: ['zebra', 'numbering'],
            textExtraction: function (node) {
                var cell_value = $(node).text();
                var sort_value = $(node).data('value');
                return (sort_value != undefined) ? sort_value : cell_value;
            }
        });
    });

    function updateRooms() {
        $('.me-room').removeClass('me-room');
        var meRooms = getMeRooms();
        for (var i = 0; i < meRooms.length; i++) {
            $('#' + meRooms[i]).addClass('me-room');
        }
    }

    function getMeRooms() {
        return JSON.parse(localStorage.getItem('meRooms'));
    }

    function func_ok(roomId) {
        var meRooms = getMeRooms();

        if (!_.isArray(meRooms)) {
            meRooms = [];
        }

        if (_.indexOf(meRooms, roomId) == -1) {
            meRooms.push(roomId);
        } else {
            meRooms = _.without(meRooms, roomId);
        }

        localStorage.setItem('meRooms', JSON.stringify(meRooms));

        updateRooms();
    }

    updateRooms();

</script>

</body>
</html>