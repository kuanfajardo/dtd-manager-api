<?php
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 8/20/16
 * Time: 8:30 PM
 */

require_once "api/APIKeyFactory.php";

var_dump(md5("jfajardo@mit.edu"));
var_dump(\Models\APIKeyFactory::verify_key("3Ha63GR28fbkknu29HUb1Qk3RO2NR9ga", "345"))
?>