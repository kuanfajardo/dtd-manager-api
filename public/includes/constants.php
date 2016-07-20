<?php

define("USER_USER",0);
$roles = $mysqli->query("SELECT id,constant,title FROM roleslkp")->fetch_all(MYSQLI_ASSOC);
foreach($roles AS $i) {
	define("USER_".$i["constant"],$i["id"]);
}


define("TYPE_LINK",1);
define("TYPE_DROPDOWN",2);
define("TYPE_HTML",3);

define("BASE_URL","dtd.mit.edu");

?>