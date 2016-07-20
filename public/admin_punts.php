<?php
require_once("includes/imports.php");
user_check_authorized([USER_HOUSE_MANAGER,USER_HONOR_BOARD]);
$user = user_id();
if(isset($_POST["submit"])) {
	if($_POST["submit"]=="0") {
		if(is_numeric($_POST["user"]) && $_POST["user"] > 0) {
			$stmt = $mysqli->prepare("INSERT INTO punts(user,given_by,comment) VALUES(?,?,?)");
			$stmt->bind_param("iis",$_POST["user"],$user,$_POST["comment"]);
			if($stmt->execute()) {
				$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Punt created.</div>";
			} else {
				$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Database error.</div>";
			}
		} else {
			$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> No User selected.</div>";
		}
	} else {
		$stmt = $mysqli->prepare("UPDATE punts SET user=?,comment=?,makeup_given_by=?,makeup_comment=?,makeup_timestamp=CURRENT_TIMESTAMP WHERE id=?");
		$stmt->bind_param("isisi",$_POST["user"],$_POST["comment"],$_POST["makeup_given_by"],$_POST["makeup_comment"],$_POST["id"]);
		if($stmt->execute()) {
			$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Punt edited.</div>";
		} else {
			$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Database error.</div>";
		}
	}
	
}
if(isset($_POST["massmakeup"]) && isset($_POST["checked"])) {
	$stmt = $mysqli->prepare("UPDATE punts SET makeup_given_by={$user},makeup_timestamp=CURRENT_TIMESTAMP WHERE id=? AND makeup_given_by<=0");
	$stmt->bind_param("i",$rid);
	foreach($_POST["checked"] as $row) {
		$rid = $row;
		$stmt->execute();
	}
}

$query3 = "SELECT id,timestamp,comment,makeup_given_by,(SELECT CONCAT(first,' ',last) FROM users WHERE id=p.user) AS givenname FROM punts p ORDER BY timestamp DESC";
$punts = $mysqli->query($query3)->fetch_all(MYSQLI_ASSOC);
$puntstats = $mysqli->query("SELECT (SELECT COUNT(*) FROM punts) AS total, (SELECT COUNT(*) FROM punts WHERE makeup_given_by <= 0) AS unchecked;")->fetch_assoc();

$users = $mysqli->query("SELECT id,CONCAT(first,' ',last) AS name FROM users ORDER BY first ASC,last ASC")->fetch_all(MYSQLI_ASSOC);
$useroptions = "<option value=\"0\">--Unassigned--</option>";
foreach($users as $u) {
	$useroptions .= "<option value=\"{$u["id"]}\">{$u["name"]}</option>";
}

echo head1("Punt Management");
?>
<script>
function editpunt(id) {
	if(id > 0) {
		$('#modal-body-content').hide();
		$('#modal-body-loading').show();
		document.getElementById("modal-title").innerHTML = "Edit";
		document.getElementById("submit-save").innerHTML = "Save Changes";
		$(".modal-new").show();

		$.post('ajax.php',{req:'punt',id:id},function(data) {
			console.log(data);
			fillmodal(data);

			$('#modal-body-loading').hide();
			$('#modal-body-content').show();
		},'json')
	} else {
		document.getElementById("modal-title").innerHTML = "New";
		document.getElementById("submit-save").innerHTML = "Confirm Punt";
		$(".modal-new").hide();

		fillmodal({id:0,user:0,given_by:'',comment:'',timestamp:'',makeup_timestamp:'',makeup_given_by:0,makeup_comment:''});
	}
	$('#modal').modal();
}
function fillmodal(data) {
	$(".modal-data-id").val(data.id);
	for(var p in data) {
		if(p == "given_by" || p == "timestamp") {
			document.getElementById("modal-data-"+p).innerHTML = data[p];
		} else if(p != "id") $("#modal-data-"+p).val(data[p]); 
	}
}
$(document).ready(function() {
	$("input[type=checkbox]").click(function(e) {
		e.stopPropagation();
	})
})
</script>
<?php
echo head2();
?>
<div class="row">
	<div class="col-xs-12">
		<form action="admin_punts.php" method="post">
			<div class="page-header"><h1>Punt Administration <small><?php echo "Total: {$puntstats["total"]}, Unchecked: {$puntstats["unchecked"]}"; ?></small></h1><button class="btn btn-custom pull-right btn-sm" style="position:relative;top:-45px;" type="button" onclick="editpunt(0)">New Punt</button><button class="btn btn-custom pull-right btn-sm" style="position:relative;top:-45px;left:-15px;" type="submit" name="massmakeup" value="massmakeup">Mass Makeup</button></div>
			<?php if(isset($msg)) echo $msg; ?>
			<table class="table table-hover">
				<thead>
					<tr>
						<th style="width:5%"><input type="checkbox" onclick="$('input[type=checkbox]').attr('checked',this.checked);"></th>
						<th style="width:15%">Date</th>
						<th style="width:20%">User</th>
						<th style="width:55%">Comment</th>
						<th style="width:5%">Makeup</th>
					</tr>
				</thead>
				<tbody>
				<?php
				if(count($punts)===0) {
					echo "<tr class=\"bg-custom\"><td colspan=\"4\" class=\"text-center\">No punts to show.</td></tr>";
				} else {
					foreach($punts as $row) {
						$date = date('D m/d/Y',strtotime($row["timestamp"]));
						if($row["makeup_given_by"] > 0) {
							$comp = "<span class=\"glyphicon glyphicon-ok\"></span>";
						} else {
							$comp = "<span class=\"glyphicon glyphicon-remove\"></span>";
						}
						echo "<tr onclick=\"editpunt({$row["id"]})\"><td><input type=\"checkbox\" name=\"checked[]\" value=\"{$row["id"]}\"></td><td>{$date}</td><td>{$row["givenname"]}</td><td>{$row["comment"]}</td><td>{$comp}</td></tr>";
					}
				}
				?>
				</tbody>
			</table>
		</form>
	</div>
</div>
<div id="modal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><span id="modal-title"> Punt</h4>
			</div>
			<form action="admin_punts.php" method="post">
				<div class="modal-body" id="modal-body">
					<div id="modal-body-loading" class="text-center" style="display:none;">
						<img src="img/loading.gif" style="height:40px;width:40px;"/>
					</div>
					<div id="modal-body-content">
						<input type="hidden" name="id" value="0" id="modal-data-id" class="modal-data-id"/>
						<p><span class="h5">User:</span> <select id="modal-data-user" name="user" class="form-control" style="display:inline-block;width:50%;"><?php echo $useroptions; ?></select></p>
						<p><span class="h5">Given By:</span> <span id="modal-data-given_by"></span></p>
						<p><span class="h5">Given At:</span> <span id="modal-data-timestamp"></span></p>
						<p class="h5">Comments:</p>
						<textarea class="form-control" rows="3" id="modal-data-comment" name="comment"></textarea>
						<p class="modal-new" style="margin-top:9px;"><span class="h5">Makeup Given By:</span> <select id="modal-data-makeup_given_by" name="makeup_given_by" class="form-control" style="display:inline-block;width:50%;"><?php echo $useroptions; ?></select></p>
						<p class="h5 modal-new">Makeup Comments:</p>
						<textarea class="form-control modal-new" rows="3" id="modal-data-makeup_comment" name="makeup_comment"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success modal-data-id" name="submit" value="0" id="submit-save"></button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php echo foot(); ?>