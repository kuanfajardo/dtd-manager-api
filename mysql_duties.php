<?php

$preset = 1; //preset default--must be integer
$timestamp = strtotime("2016-7-18 12:00pm");//start date
$end = strtotime('2016-7-19 12:00pm'); //end date

//die();

require_once("public/includes/db.php");
$timestamp -= 24*60*60*(date('N',$timestamp)%7);
$date = date('D m/d/Y',$timestamp);
echo $date;
while($timestamp <= $end) {
	$mysqli->query("INSERT INTO houseduties(duty,start) SELECT duty,FROM_UNIXTIME({$timestamp}+24*60*60*r.day) FROM housedutiesdefaults r WHERE preset={$preset};");
	$timestamp += 24*60*60*7;
	//echo "1";
}
?>
