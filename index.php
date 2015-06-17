<?php
date_default_timezone_set('Europe/Moscow');
mb_internal_encoding('UTF-8');
$result = json_decode(file_get_contents('http://steamga.me/data/api/all.json'));

$array = array();
foreach($result as $res) {
$res->lvl_per_wh = $res->wormholes > 0 ? $res->level / $res->wormholes : 0;
$array[] = $res;
}

usort($array, function($a, $b){
if ($a->lvl_per_wh == $b->lvl_per_wh) {
        return 0;
    }
    return ($a->lvl_per_wh > $b->lvl_per_wh) ? -1 : 1;
});

// render
echo '<table>';
foreach($array as $res) {
echo '<tr>';

echo "<td>{$res->id}</td><td>{$res->lvl_per_wh }</td>";

//var_dump($res);
echo '</tr>';
}

echo '</table>';