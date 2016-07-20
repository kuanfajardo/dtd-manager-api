<?php
function redirect_to($url) {
	header("Location: " . $url);
	exit;
}

//user functions
function user_id() {
	return (isset($_SESSION["user_id"]))?$_SESSION["user_id"]:-1;
}
function user_name() {
	return (isset($_SESSION["user_name"]))?$_SESSION["user_name"]:"";
}
function user_privileges() {
	return (isset($_SESSION["user_roles"])) ? $_SESSION["user_roles"] : [];
}
function user_authorized($roles) {
	$up = user_privileges();
	if(!is_array($roles)) {
		$roles = [$roles];
	}
	foreach($roles as $role) {
		if(in_array($role,$up)) return true;
	}
	return in_array(USER_ADMIN,$up);//admin override
}
function user_check_authorized($role) {
	if(!user_authorized($role)) redirect_to("index.php?redirect=" . urlencode($_SERVER["REQUEST_URI"]));
}
function user_email() {
	return (isset($_SESSION["user_email"]))?$_SESSION["user_email"]:false;
}
function user_certificates() {
	return isset($_SERVER["SSL_CLIENT_S_DN_CN"]);
}

function room_from_number($num) {
	if($num==-1) return "On Campus";
	$floor = floor($num/100);
	$frontrear = floor($num/10)%10;
	$side = $num%10;
	switch($floor) {
		case 0: $rtn = "Basement ";break;
		case 1: $rtn = "1st ";break;
		case 2: $rtn = "2nd ";break;
		case 3: $rtn = "3rd ";break;
		case 4: $rtn = "4th ";break;
	}
	switch($frontrear) {
		case 0:
			$rtn .= "Front";
			$rtn .= ($side?" Right":"");
			break;
		case 1:
			$rtn .= "Rear " . ($side?"Right":"Left");
			break;
		case 2:
			$rtn .= "Mid";
			break;
		case 3:
			$rtn = "Skylight";
			break;
		default: $rtn .= "Unknown";break;
	}
	return $rtn;
}

function printit($arr) {
	echo "<pre>";
	print_r($arr);
	echo "</pre>";
}


//email functions
function send_email($to,$subject,$message) {
	global $mysqli;
	$headers = "Content-Type:text/plain;charset=UTF-8\r\nFrom: Delts Manager<noreply@".BASE_URL.">\r\nX-Mailer: PHP/".phpversion();
	$success = mail($to,$subject,$message,$headers)?1:0;
	$stmt = $mysqli->prepare("INSERT INTO emails(recipient,subject,message,headers,success) VALUES(?,?,?,?,?)");
	$stmt->bind_param("ssssi",$to,$subject,$message,$headers,$success);
	$stmt->execute();
	return $success;
}
// function send_email_html($to,$subject,$message) {
// 	$m = "<html><head><title>{$subject}</title></head><body>{$message}</body></html>";
// 	$headers = "From: Delts Manager<noreply@" . BASE_URL . ">\r\nX-Mailer: PHP/".phpversion()."\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n";
	
// 	$stmt = $mysqli->prepare("INSERT INTO emails(recipient,subject,message,headers) VALUES(?,?,?,?)");
// 	$stmt->bind_param("ssss",$to,$subject,$m,$headers);
// 	$stmt->execute();

// 	return mail($to,$subject,$m,$headers);
// }
function error_email($usertypes,$message) {
	global $mysqli;
	$roles = implode(',',$usertypes);
	$query = "SELECT email FROM users WHERE id IN(SELECT user FROM roles WHERE role IN({$roles}))";
	$data = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
	$to = "";
	foreach($data as $row) {
		$to .= $row["email"] . ",";
	}
	$to = rtrim($to,",");
	send_email($to,"Delts Manager Error Message","Delts Manager website encountered an error.\r\n\r\nMessage: {$message}\r\n\r\nCheers,\r\n\tDM");
}

?>