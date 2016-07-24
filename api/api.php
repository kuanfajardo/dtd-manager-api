<?php
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/24/16
 * Time: 8:02 PM
 */

// TODO: make sure this is correct path
require_once ('../public/includes/db.php');
require_once ('../public/includes/functions.php');

// Requests from the same server don't have a HTTP_ORIGIN header
if(!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

// PUBLIC STATIC VOID MAIN [first responder]
try {
    $API = new DeltsManagerAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}