<?php
//Extract data from database in JSON format
    header('Content-type: application/json; charset=utf-8');
    function Grab($name) {
        if (isset($_GET[$name])){return $_GET[$name];}
        else {return NULL;}
    }
    $date = Grab('date');
    $db = new SQLite3('/home/pi/GPIO/tempdata.db');
    $results = $db->query("SELECT tdate, ttime, temp, humid, abshum 
			    FROM temps WHERE tdate = '".$date."' ORDER BY ttime");
    $row = $results->fetchArray();
    setlocale(LC_TIME, 'en_US');
    $tstamp = strftime('%s', strtotime($row[0]."T".$row[1]))*1000;
    echo "{\"label\":\"".$date."\",\"data\":";
    echo "[[".$tstamp.",".$row[2].",".$row[3].",".$row[4]."]";

    while ($row = $results->fetchArray()) {
        $tstamp = strftime('%s', strtotime($row[0]."T".$row[1]))*1000;
	echo ",[".$tstamp.",".$row[2].",".$row[3].",".$row[4]."]";
    }
    echo "]}";
?>
