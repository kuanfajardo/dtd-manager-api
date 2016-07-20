<?php
require_once("includes/imports.php");
user_check_authorized(0);
require_once("includes/db.php");
$user_id = $mysqli->real_escape_string($_SESSION["user_id"]);

if(isset($_POST["submit"]) && $_POST["submit"]=="changepassword") {
	if(strlen($_POST["password"]) >= 6) {
		if($_POST["password"]===$_POST["confirm"]) {
			$stmt = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
			$stmt->bind_param("si",$password,$user_id);
			$password = password_hash($_POST["password"],PASSWORD_BCRYPT);
			if(!$stmt->execute()) {
				$msg_password = "<div class=\"alert alert-danger\"><strong>Error!</strong> Database update error.</div>";
			} else {
				$msg_password = "<div class=\"alert alert-success\"><strong>Success!</strong> Password updated.</div>";
			}
		} else {
			$msg_password = "<div class=\"alert alert-danger\"><strong>Error!</strong> Passwords don't match.</div>";
		}
	} else {
		$msg_password = "<div class=\"alert alert-danger\"><strong>Error!</strong> Password must be at least 6 characters.</div>";
	}
}

if(isset($_GET["request"]) && is_numeric($_GET["request"])) {
	$duty_id = $mysqli->real_escape_string($_GET["request"]);
	$res = $mysqli->prepare("UPDATE houseduties SET checker=-1,checktime=CURRENT_TIMESTAMP WHERE id=? AND checker=0 AND user=?");
	$res->bind_param("ii",$duty_id,$user_id);
	$res->execute();
	if($res->affected_rows > 0) {
		$user_name = user_name();
		send_email(user_email(),"Checkoff Requested","{$user_name},\r\n\r\nYou just requested a checkoff for a duty.\r\n\r\nCheers,\r\nDM");
		send_email("dtd-checkers@mit.edu","Checkoff Requested","Checkers,\r\n\r\n{$user_name} just requested a checkoff. Visit http://".BASE_URL."/checker_dashboard.php to give them a checkoff.\r\n\r\nCheers,\r\nDM");
		redirect_to("dashboard.php");
	} else {
		redirect_to("dashboard.php?error=1");
	}
}

$sunday = strtotime("last Sunday 12:00am")-100;
$fourhours = time()-4*60*60;
$limit = isset($_GET["all"])?"":" LIMIT 2";
$query2 = "(SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$user_id} AND checker <= 0 AND start > FROM_UNIXTIME({$sunday}) ORDER BY start ASC,dutyname ASC) UNION (SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$user_id} AND start > FROM_UNIXTIME({$sunday}) AND checker > 0 ORDER BY start DESC,dutyname ASC{$limit});";
$houseduties = $mysqli->query($query2)->fetch_all(MYSQLI_ASSOC);
$query3 = "SELECT timestamp,comment,makeup_given_by,IF(p.given_by>0,(SELECT CONCAT(first,' ',last) FROM users WHERE id=p.given_by),'Delts Manager') AS givenname FROM punts p WHERE user={$user_id} ORDER BY timestamp DESC";
$punts = $mysqli->query($query3)->fetch_all(MYSQLI_ASSOC);
echo head("Dashboard");
?>
<div class="row">
	<div class="col-md-9">
		<div class="row">
			<div class="col-xs-12">
				<a href="houseduties.php"><h4>House Duties</h4></a>
				<?php if(isset($_GET["error"]) && $_GET["error"]=="1") echo $msg_main = "<div class=\"alert alert-danger\"><strong>Error!</strong> No checkoff found!</div>"; ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th style="width:25%">Date</th>
							<th style="width:60%">Duty</th>
							<th style="width:15%">Checkoff</th>
						</tr>
					</thead>
					<tbody>
					<?php
					if(count($houseduties)===0) {
						echo "<tr><td colspan=\"3\" class=\"text-center\">You have not signed up for any house duties</td></tr>";
					} else {
						foreach($houseduties as $row) {
							$date = date('D m/d/Y',strtotime($row["time"]));
							if($row["checker"] > 0) {
								$comp = "<span class=\"glyphicon glyphicon-ok\"></span>";
							} elseif($row["checker"]==-10) {
								$comp = "<span class=\"glyphicon glyphicon-alert\"></span>";
							} else {
								$dis = $row["checker"]==0?"":" disabled";
								$comp = "<a class=\"btn btn-custom btn-xs\" href=\"dashboard.php?request={$row["id"]}\"{$dis}>Request</a>";
							}
							echo "<tr><td>{$date}</td><td>{$row["dutyname"]}</td><td>{$comp}</td></tr>";
						}
					}
					?>
					</tbody>
				</table>
				<h4>Punts</h4>
				<table class="table table-striped">
					<thead>
						<tr>
							<th style="width:15%">Date</th>
							<th style="width:20%">Given by</th>
							<th style="width:55%">Comment</th>
							<th style="width:10%">Makeup</th>
						</tr>
					</thead>
					<tbody>
					<?php
					if(count($punts)===0) {
						echo "<tr><td colspan=\"4\" class=\"text-center\">Congrats! You have no punts!</td></tr>";
					} else {
						foreach($punts as $row) {
							$date = date('D m/d/Y',strtotime($row["timestamp"]));
							if($row["makeup_given_by"] > 0) {
								$comp = "<span class=\"glyphicon glyphicon-ok\"></span>";
							} else {
								$comp = "<span class=\"glyphicon glyphicon-remove\"></span>";
							}
							echo "<tr><td>{$date}</td><td>{$row["givenname"]}</td><td>{$row["comment"]}</td><td>{$comp}</td></tr>";
						}
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
		<?php /*
		<div class="row">
			<div class="col-xs-12">
				<a href="parties.php"><h4>Upcoming Parties</h4></a>
				<table class="table table-striped">
					<thead>
						<tr>
							<th style="width:25%">Date</th>
							<th style="width:60%">Duty</th>
							<th style="width:15%">Completed</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$parties = [];
					if(count($parties)===0) {
						echo "<tr><td colspan=\"3\" class=\"text-center\">There are no upcoming parties listed here.</td></tr>";
					} else {
						foreach($parties as $row) {
							$date = date('D m/d/Y',strtotime($row["time"]));
							$comp = $row["checker"]?"ok":"remove";
							echo "<tr><td>{$date}</td><td>{$row["duty"]}</td><td><span class=\"glyphicon glyphicon-{$comp}\"></span></td></tr>";
						}
					}
					?>
					</tbody>
				</table>
			</div>
		</div> */ ?>
	</div>
	<div class="col-md-3">
		<div class="well">
			<strong>Money Earned:</strong> $<?php 
			$num = $mysqli->query("SELECT COUNT(*) AS c FROM houseduties WHERE user={$user_id} AND checker>0 AND start>'2016-5-20' AND start<'2016-8-22'")->fetch_assoc()["c"];
			echo money_format('%i',$num*20);
			?><br/><!--<a href="#">Prior Payments</a>-->
		</div>
		<h4>Set Password (for non-certificate devices)</h4>
		<?php if(isset($msg_password)) echo $msg_password; ?>
		<form action="dashboard.php" method="post">
			<div class="form-group">
				<label class="control-label" for="password">New Password</label>
				<input type="password" class="form-control" name="password" id="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;"/>
			</div>
			<div class="form-group">
				<label class="control-label" for="confirm">Confirm Password</label>
				<input type="password" class="form-control" name="confirm" id="confirm" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;"/>
			</div>
			<button type="submit" class="col-xs-12 btn btn-custom" name="submit" value="changepassword">Change Password</button>
		</form>
	</div>
</div>
<?php echo foot(); ?>