<?php
session_start();

$temp = explode('/',$_SERVER["SCRIPT_NAME"]);
$_SERVER["THIS"] = $temp[count($temp)-1];

require_once("db.php");
require_once("constants.php");
require_once("functions.php");
require_once("functions_html.php");
?>