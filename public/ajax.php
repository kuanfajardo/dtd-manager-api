<?php
require_once("includes/imports.php");
if(isset($_POST["req"])) {
	if($_POST["req"]=="checkoff" && isset($_POST["id"]) && is_numeric($_POST["id"]) && user_authorized([USER_HOUSE_MANAGER,USER_CHECKER])) {
		$id = $mysqli->real_escape_string($_POST["id"]);
		$query = "SELECT id,user,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS title,(SELECT description FROM housedutieslkp WHERE id=r.duty) AS description,DATE_FORMAT(start,'%a %c/%e') AS start,(CASE WHEN r.checker>0 THEN (SELECT CONCAT(first,' ',last) FROM users WHERE id=r.checker) ELSE 'N/A' END) AS checker, checkcomments AS comments FROM houseduties r WHERE id={$id};";
		$data = $mysqli->query($query);
		$data = $data->fetch_assoc();
		
		
		echo json_encode($data);
	} else if($_POST["req"] == "manualcheckoffs" && isset($_POST["date"]) && user_authorized([USER_HOUSE_MANAGER,USER_CHECKER])) {
		$d = $mysqli->real_escape_string(date('Y-m-d',strtotime($_POST["date"])));
		$query = "SELECT id,(CASE WHEN r.checker>0 THEN 'ok' ELSE 'remove' END) AS checkoff,(CASE WHEN r.user=0 THEN '<strong>Unassigned</strong>' ELSE (SELECT CONCAT(first,' ',last) FROM users WHERE id=r.user)END) AS user,r.user AS userid,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS duty FROM houseduties r WHERE start='{$d}'";
		echo json_encode($mysqli->query($query)->fetch_all(MYSQLI_ASSOC));
	} else if($_POST["req"] == "type" && isset($_POST["id"]) && is_numeric($_POST["id"]) && user_authorized(USER_ADMIN)) {
		$id = $mysqli->real_escape_string($_POST["id"]);
		$query = "SELECT role FROM roles WHERE user={$id}";
		$d = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
		$roles = [];
		foreach($d as $i) {
			$roles[] = $i["role"];
		}
		echo json_encode(["id"=>$id,"roles"=>$roles]);
	} else if($_POST["req"] == "punt" && isset($_POST["id"]) && is_numeric($_POST["id"])) {
		$id = $mysqli->real_escape_string($_POST["id"]);
		$query = "SELECT id,user,IF(given_by>0,(SELECT CONCAT(first,' ',last) FROM users WHERE id=given_by),'Delts Manager') AS given_by,timestamp,comment,makeup_timestamp,makeup_given_by,makeup_comment FROM punts WHERE id={$id}";
		echo json_encode($mysqli->query($query)->fetch_assoc());
	}
} else {
	die("Invalid Request");
}
?>