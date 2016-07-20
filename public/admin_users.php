<?php
require_once("includes/imports.php");
user_check_authorized(USER_ADMIN);

/*if(isset($_POST["type"])) {
	if(is_numeric($_POST["type"]) && is_numeric($_POST["typeuser"]) && $_POST["type"] >= 0 && $_POST["type"] <= 2) {
		$stmt = $mysqli->prepare("UPDATE users SET user_privileges=? WHERE id=?");
		$stmt->bind_param("ii",$_POST["type"],$_POST["typeuser"]);
		$stmt->execute();
		if($stmt->affected_rows > 0) {
			$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> User type updated.</div>";
		} else {
			$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> User not found.</div>";
		}
	} else {
		$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Invalid user or type.</div>";
	}
}*/
if(isset($_POST["typeuser"]) && is_numeric($_POST["typeuser"])){
	$user = $mysqli->real_escape_string($_POST["typeuser"]);
	$mysqli->query("DELETE FROM roles WHERE user={$user}");
	$query = "INSERT INTO roles(user,role) VALUES";
	$q = "";
	foreach($roles as $r) {
		if(isset($_POST["type_".$r["id"]])) $q .= "({$user},{$r["id"]}),";
	}
	$q = rtrim($q,",");
	if(strlen($q)==0 || $mysqli->query($query . $q)) {
		$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Types updated.</div>";
	} else {
		$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Type update failed.</div>";
	}
} elseif(isset($_POST["changepassword"]) && is_numeric($_POST["changepassword"]) && isset($_POST["password"])) {
	$user = $mysqli->real_escape_string($_POST["changepassword"]);
	$pw = $mysqli->real_escape_string(password_hash($_POST["password"],PASSWORD_BCRYPT));
	if($mysqli->query("UPDATE users SET password='{$pw}' WHERE id={$user}")) {
		$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Password updated.</div>";
	} else {
		$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Password update failed.</div>";
	}
}

$users = $mysqli->query("SELECT id,first,middle,last,nickname,pledgeyear,pledgenumber,email,room,(SELECT GROUP_CONCAT(title SEPARATOR ', ') FROM roleslkp WHERE id IN(SELECT role FROM roles WHERE user=u.id)) AS type FROM users u ORDER BY first ASC, middle ASC, last ASC, pledgeyear ASC");
echo $mysqli->error;
$users = $users->fetch_all(MYSQLI_ASSOC);
echo head1("User Management");
?>
<script>
function changetype(id,t) {
	document.getElementById("typesubmit").value = 0;
	$(".type_checkbox").prop("checked",false);
	var c = t.parentElement.parentElement.parentElement.parentElement.parentElement.children[0].children[0];
	document.getElementById("typemodal-name").innerHTML = c.innerHTML;
	$("#typemodal-content").hide();
	$("#typemodal-loading").show();
	$.post('ajax.php',{req:'type',id:id},function(data) {
		document.getElementById("typesubmit").value = data.id;
		for(var i = 0; i < data.roles.length; i++) {
			$("#type_"+data.roles[i]).prop("checked",true);
		}
		$("#typemodal-loading").hide();
		$("#typemodal-content").show();
	},'json');
	$("#typemodal").modal();
}
function changepw(id,name) {
	document.getElementById("pwmodal-name").innerHTML = name;
	document.getElementById("pw-id").value = id;
	$("#pwmodal").modal();
}
</script>
<?php
echo head2();
?>
<div class="row">
	<div class="col-xs-12">
		<div class="page-header"><h1>Manage Users</h1><!--<a class="btn btn-custom pull-right btn-sm" style="position:relative;top:-45px;" href="admin_users_new.php">New User</a>--></div>
		<?php if(isset($msg)) echo $msg; ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th style="width:30%">Name</th>
					<th style="width:15%">Pledge</th>
					<th style="width:15%">Room</th>
					<th style="width:15%">Officer</th>
					<th style="width:25%">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($users as $row) {
					$name = $row["first"] . " ";
					if(strlen($row["middle"])) $name .= $row["middle"] . " ";
					$name .= $row["last"];
					if(strlen($row["nickname"])) $name .= " (" . $row["nickname"] . ")";
					$room = room_from_number($row["room"]);
					$type = strlen($row["type"]) > 0?$row["type"]:"None";
					echo "<tr><td><a href=\"mailto:{$row["email"]}\">{$name}</a></td><td>{$row["pledgeyear"]} #{$row["pledgenumber"]}</td><td>{$room}</td><td>{$type}</td><td>
					<div class=\"btn-group\">
						<button class=\"btn btn-xs dropdown-toggle btn-custom\" type=\"button\" data-toggle=\"dropdown\">Action <span class=\"caret\"></span></button>
						<ul class=\"dropdown-menu\">
							<li><a href=\"javascript:void(0)\" onclick=\"changetype({$row["id"]},this)\">Change Type</a></li>
							<li><a href=\"javascript:void(0)\" onclick=\"changepw({$row["id"]},'{$name}')\">Change Password</a></li>
						</ul>
					</div>
					</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>
</div>
<div class="modal fade" id="typemodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Change Roles for <span id="typemodal-name"></span></h4>
			</div>
			<form action="admin_users.php" method="post">
				<div class="modal-body">
					<div id="typemodal-content" style="display:none;">
					<?php
					foreach($roles as $r) {
						echo "<div class=\"checkbox\"><label><input type=\"checkbox\" class=\"type_checkbox\" name=\"type_{$r["id"]}\" id=\"type_{$r["id"]}\"/> {$r["title"]}</label></div>";
					}
					?>
					</div>
					<div id="typemodal-loading">
						<img src="img/loading.gif" style="height:40px;width:40px;"/>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-custom" name="typeuser" id="typesubmit">Save changes</button>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="modal fade" id="pwmodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Change Password for <span id="pwmodal-name"></span></h4>
			</div>
			<form action="admin_users.php" method="post">
				<div class="modal-body">
					<div class="form-group">
						<label class="control-label" for="pw-input">Password</label>
						<input type="password" class="form-control" name="password" id="pw-input"/>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-custom" name="changepassword" id="pw-id">Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php /*
<div class="modal fade" id="newmodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Edit User</h4>
			</div>
			<form action="admin_users.php" method="post">
				<div class="modal-body">
					<div class="form-group">
						<label class="control-label" for="first">First Name</label>
						<input type="text" class="form-control" name="first" id="first" placeholder="First Name"/>
					</div>
					<div class="form-group">
						<label class="control-label" for="first">Middle Name</label>
						<input type="text" class="form-control" name="middle" id="middle" placeholder="Middle Name"/>
					</div>
					<div class="form-group">
						<label class="control-label" for="first">Last Name</label>
						<input type="text" class="form-control" name="last" id="last" placeholder="Last Name"/>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-custom" name="editsubmit" value="submit" id="editsubmit">Save Changes</button>
				</div>
			</form>
		</div>
	</div>
</div>
*/ ?>
<?php echo foot(); ?>