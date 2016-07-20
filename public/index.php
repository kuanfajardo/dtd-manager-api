<?php
require_once("includes/imports.php");

if(isset($_GET["logout"])) {
	session_unset();
	redirect_to("index.php");
} else if(user_authorized(0) && isset($_GET["redirect"])) {
	redirect_to($_GET["redirect"]);
} else if(user_authorized(0)) {
	redirect_to("dashboard.php");
} else if(isset($_SERVER["SSL_CLIENT_S_DN_CN"]) && $_SERVER["SSL_CLIENT_S_DN_CN"]) {
	$email = strtolower($_SERVER["SSL_CLIENT_S_DN_Email"]);
	$stmt = $mysqli->prepare("SELECT id,email,CONCAT(first,' ',last) AS name FROM users WHERE email=?");
	$stmt->bind_param("s",$email);
	$stmt->bind_result($res_id,$res_email,$res_name);
} else if(isset($_POST["submit"]) && isset($_POST["email"]) && isset($_POST["password"])) {
	if(filter_var($_POST["email"],FILTER_VALIDATE_EMAIL)) {
		if(strlen($_POST["password"]) >= 6) {
			$stmt = $mysqli->prepare("SELECT id,password,email,CONCAT(first,' ',last) AS name FROM users WHERE email=?");
			$stmt->bind_param("s",$_POST["email"]);
			$stmt->bind_result($res_id,$res_pw,$res_email,$res_name);
		} else {
			$success = 3;
		}
	} else {
		$success = 2;
	}
}

if(isset($stmt)) {
	$stmt->execute();
	$stmt->fetch();
	$stmt->free_result();
	$stmt2 = $mysqli->prepare("INSERT INTO logins(user,success) VALUES(?,?)");
	$stmt2->bind_param("ii",$res_id,$success);
	if($res_id) {
		if(!isset($res_pw) || password_verify($_POST["password"],$res_pw)) {
			$success = isset($res_pw)?1:2;
			$_SESSION["user_id"] = $res_id;
			$_SESSION["user_email"] = $res_email;
			$_SESSION["user_name"] = $res_name;
			
			$res = $mysqli->query("SELECT role FROM roles WHERE user={$res_id};")->fetch_all(MYSQLI_NUM);
			$_SESSION["user_roles"] = [0];
			foreach($res as $r) {
				$_SESSION["user_roles"][] = $r[0];
			}
			$stmt2->execute();
			if(isset($_GET["redirect"])) {
				redirect_to($_GET["redirect"]);
			} else {
				redirect_to("dashboard.php");
			}
		} else {
			$success = 0;
			$stmt2->execute();
		}
	} else {
		$success = 1;
	}
}

echo head("Homepage");
?>
<div class="row">
	<div class="col-xs-12">
		<div class="jumbotron">
			<div class="row">
				<div class="col-xs-12 text-center">
					<h1>Welcome to Delts</h1>
					<p>Log in for access.</p>
					<p>If you don't have credentials, you were probably looking for <a href="//delts.mit.edu">delts.mit.edu</a></p>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-4 col-sm-offset-4">
					<?php
					if(isset($success)) {
						switch($success) {
							case 0: $msg = "User/Password do not match. This is not necessarily your MIT password";break;
							case 1: $msg = "No user found";break;
							case 2: $msg = "Invalid e-mail";break;
							case 3: $msg = "Password must be at least 6 characters";break;
						}
						$msg = $success?"No user found":"User/Password do not match";
						echo "<div class=\"alert alert-danger\"><strong>Error</strong> {$msg}.</div>";
					}
					?>
					<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
						<div class="form-group">
							<label class="control-label" for="email">E-Mail</label>
							<input type="email" class="form-control" name="email" id="email" placeholder="E-Mail"/>
						</div>
						<div class="form-group">
							<label class="control-label" for="password">Password</label>
							<input type="password" class="form-control" name="password" id="email" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;"/>
						</div>
						<button type="submit" class="col-xs-5 btn btn-custom" name="submit" value="submit">Log In</button>
						<a href="https://<?php echo BASE_URL; ?>:444<?php echo $_SERVER["REQUEST_URI"]; ?>" class="col-xs-5 col-xs-offset-2 btn btn-custom">Certificates</a>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo foot(); ?>