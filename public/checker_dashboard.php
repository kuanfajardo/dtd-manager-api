<?php
require_once("includes/imports.php");
user_check_authorized([USER_HOUSE_MANAGER,USER_CHECKER]);
require_once("includes/db.php");


if(isset($_POST["checkoff"])) {
	$checker = ($_POST["checkoff"]==1)?user_id():0;
	$stmt = $mysqli->prepare("UPDATE houseduties SET checktime=CURRENT_TIMESTAMP,checkcomments=?,user=?,checker={$checker} WHERE id=?");
	$stmt->bind_param("sii",$_POST["comments"],$_POST["user"],$_POST["id"]);
	$stmt->execute();

	$stmt = $mysqli->prepare("SELECT email,first FROM users WHERE id=(SELECT user FROM houseduties WHERE id=?)");
	$stmt->bind_param("i",$_POST["id"]);
	$stmt->bind_result($user_email,$user_name);
	$stmt->execute();
	$stmt->fetch();
	$stmt->free_result();
	$checker_name = user_name();
	if($_POST["checkoff"]==1) {
		$message = "{$user_name},\r\n\r\n{$checker_name} just checked off one of your duties.\r\n\r\nCheers,\r\n\tDM";
		$subject = "Checked Off";
	} else {
		$message = "{$user_name},\r\n\r\n{$checker_name} just modified one of your duties.\r\n\r\nCheers,\r\n\tDM";
		$subject = "Checkoff Comment (No Checkoff)";
	}
	send_email($user_email,$subject,$message);
} else if(isset($_GET["quick"]) && is_numeric($_GET["quick"])) {
	$it = $mysqli->real_escape_string($_GET["quick"]);
	$mysqli->query("UPDATE houseduties SET checktime=CURRENT_TIMESTAMP,checker=".user_id()." WHERE id={$it};");
	$stmt = $mysqli->prepare("SELECT email,first FROM users WHERE id=(SELECT user FROM houseduties WHERE id=?)");
	$stmt->bind_param("i",$_GET["quick"]);
	$stmt->bind_result($user_email,$user_name);
	$stmt->execute();
	$stmt->fetch();
	$stmt->free_result();
	$checker_name = user_name();
	$message = "{$user_name},\r\n\r\n{$checker_name} just checked off one of your duties.\r\n\r\nCheers,\r\n\tDM";
	$subject = "Checked Off";
	
	send_email($user_email,$subject,$message);
	redirect_to("checker_dashboard.php");
}

//database links
$checkoffs = $mysqli->query("SELECT id,(SELECT CONCAT(first,' ',last) FROM users WHERE id=r.user) AS name,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS duty,start FROM houseduties r WHERE checker=-1;");
$checkoffs = $checkoffs->fetch_all(MYSQLI_ASSOC);

if(isset($_GET["limit"]) && is_numeric($_GET["limit"])) {
	if($_GET["limit"]=="0") {
		$limit = "";
	} else {
		$l = $mysqli->real_escape_string($_GET["limit"]);
		$limit = " LIMIT {$l}";
	}
} else {
	$limit = " LIMIT 4";
}
$query = "SELECT id,(SELECT CONCAT(first,' ',last) FROM users WHERE id=r.user) AS name,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS duty,checktime FROM houseduties r WHERE checker > 0 ORDER BY checktime DESC,duty ASC{$limit};";
$old_checkoffs = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

$users = $mysqli->query("SELECT id,CONCAT(first,' ',last) AS name FROM users ORDER BY first ASC,last ASC")->fetch_all(MYSQLI_ASSOC);
$useroptions = "<option value=\"0\">--Unassigned--</option>";
foreach($users as $u) {
	$useroptions .= "<option value=\"{$u["id"]}\">{$u["name"]}</option>";
}
echo head1("Checker Dashboard");
?>
<script>
function confirmmodal(id) {
	$("#modal-body-content").hide();
	$("#modal-body-loading").show();
	$.post('ajax.php',{req:'checkoff',id:id},function(data) {
		var fields = ['start','title','description','checker'];
		for(var i = 0; i < fields.length; i++) {
			f = fields[i];
			document.getElementById('modal-data-'+f).innerHTML = data[f];
		}
		document.getElementById('modal-data-id').value = data.id;
		document.getElementById('modal-data-comments').value = data.comments;
		$('select#modal-data-user').val(data.user);

		$("#modal-body-loading").hide();
		$("#modal-body-content").show();
	},'json');

	$("#modal").modal();
}
function manualmodal() {
	var d = $("#manual-date").val();
	document.getElementById("manual-modal-date").innerHTML = d;
	$("#manual-modal-body-content").hide();
	$("#manual-modal-body-loading").show()
	$.post('ajax.php',{req:'manualcheckoffs',date:d},function(data) {
		var b = "";
		if(data.length > 0) {
			data.forEach(function(val) {
				b += "<tr><td>" + val.user + "</td><td><span class=\"glyphicon glyphicon-" + val.checkoff + "\"></span> " + val.duty + "</td><td><button type=\"button\" onclick=\"confirmmodal(" + val.id + ")\" class=\"btn btn-custom btn-sm\">Open</button>";
				if(val.checkoff==="remove") b += " <a class=\"btn btn-custom btn-sm\" href=\"checker_dashboard.php?quick="+val.id+"\">Checkoff</a>";
				b += "</td></tr>";
			});
		} else {
			b = "<tr class=\"bg-custom\"><td colspan=\"3\" class=\"text-center\">No Assigned Duties on Date</td></tr>";
		}
		document.getElementById("manual-modal-tbody").innerHTML = b;
		$("#manual-modal-body-loading").hide();
		$("#manual-modal-body-content").show()
	},'json');
	$("#manual-modal").modal();
}
function setlimit(l) {
	var limit;
	if(l==-1) {
		limit = "";
	} else if(l==0) {
		limit = "?limit=0";
	} else {
		limit = "?limit=" + l;
	}
	document.location = "checker_dashboard.php" + limit;
}
$(document).ready(function() {
	var now = new Date();

	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);

	var today = now.getFullYear()+"-"+(month)+"-"+(day);

	$("#manual-date").val(today);
});
</script>
<?php
echo head2();
?>
<div class="row">
	<div class="col-sm-9">
		<h4>Requested Checkoffs</h4>
		<table class="table table-hover">
			<thead>
				<tr>
					<th style="width:25%;">Name</th>
					<th style="width:50%;">Duty</th>
					<th style="width:25%">Due Date</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if(count($checkoffs) > 0) { 
					foreach($checkoffs as $row) {
						$date = date('D n/j',strtotime($row["start"]));
						echo "<tr onclick=\"confirmmodal({$row["id"]})\"><td>{$row["name"]}</td><td>{$row["duty"]}</td><td>{$date}</td></tr>";
					}
				} else {
					echo "<tr class=\"bg-custom\"><td colspan=\"3\" class=\"text-center\">No Duties to Check Off</td></tr>";
				}
				?>
			</tbody>
		</table>
		<h4>
			Given Checkoffs 
			<select class="pull-right form-control" style="display:inline-block;width:150px;position:relative;top:-10px;" oninput="setlimit(this.value)">
				<option value="-1">--View--</option>
				<?php
					$o = [10,25,50,100,0];
					foreach($o as $i) {
						$val = $i==0?"Unlimited":$i;
						$selected = (isset($_GET["limit"]) && $i==$_GET["limit"])?" selected":"";
						echo "<option value=\"{$i}\"{$selected}>{$val}</option>";
					}
				?>
			</select>
		</h4>
		<table class="table table-hover">
			<thead>
				<tr>
					<th style="width:25%;">Name</th>
					<th style="width:50%;">Duty</th>
					<th style="width:25%">Checkoff Time</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if(count($old_checkoffs) > 0) { 
					foreach($old_checkoffs as $row) {
						$date = date('D n/j h:i',strtotime($row["checktime"]));
						echo "<tr onclick=\"confirmmodal({$row["id"]},true)\"><td>{$row["name"]}</td><td>{$row["duty"]}</td><td>{$date}</td></tr>";
					}
				} else {
					echo "<tr class=\"bg-custom\"><td colspan=\"3\" class=\"text-center\">No Duties to List</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="col-sm-3">
		<h4>Manual Checkoff/Duty Editor</h4>
		<div class="form-group">
			<label class="control-label">Date</label>
			<input type="date" class="form-control" id="manual-date" placeholder="mm/dd/yyyy"/>
		</div>
		<button type="button" onclick="manualmodal()" class="col-xs-12 btn btn-custom">Get Duties</button>
		<br/>
		<h4>Money Owed</h4>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>Money</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$d = $mysqli->query("SELECT id,CONCAT(first,' ',last) AS name,(SELECT COUNT(*) FROM houseduties WHERE user=u.id AND checker>0 AND start>'2016-5-20' AND start<'2016-8-22') AS c FROM users u ORDER BY (c>0) DESC,name ASC;")->fetch_all(MYSQLI_ASSOC);
				foreach($d as $val) {
					if($val["c"]==0) break;
					echo "<tr><td>{$val["name"]}</td><td>\$".money_format("%i",$val["c"]*20)."</td></tr>";

				}
				?>
			</tbody>
		</table>
	</div>
</div>
<div id="manual-modal" class="modal fade" tabindex="-2" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Checkoffs for <span id="manual-modal-date"></span></h4>
			</div>
			<form action="checker_dashboard.php" method="post">
				<div class="modal-body" id="modal-body">
					<div id="manual-modal-body-loading" class="text-center">
						<img src="img/loading.gif" style="height:40px;width:40px;"/>
					</div>
					<div id="manual-modal-body-content" style="display:none;">
						<table class="table">
							<thead>
								<tr>
									<th>User</th>
									<th>Checkoff/Duty</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody id="manual-modal-tbody">

							</tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				</div>
			</form>
		</div>
	</div>
</div>
<div id="modal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Confirm Checkoff</h4>
			</div>
			<form action="checker_dashboard.php" method="post">
				<div class="modal-body" id="modal-body">
					<div id="modal-body-loading" class="text-center">
						<img src="img/loading.gif" style="height:40px;width:40px;"/>
					</div>
					<div id="modal-body-content" style="display:none;">
						<input type="hidden" name="id" value="0" id="modal-data-id"/>
						<p><span class="h5">User:</span> <select id="modal-data-user" name="user" class="form-control" style="display:inline-block;width:50%;"><?php echo $useroptions; ?></select></p>
						<p><span class="h5">Date:</span> <span id="modal-data-start"></span></p>
						<p><span class="h5">Duty:</span> <span id="modal-data-title"></span></p>
						<p class="h5">Duty Description:</p>
						<p id="modal-data-description"></p>
						<p><span class="h5">Checker:</span> <span id="modal-data-checker"></span></p>
						<p class="h5">Checker Comments:</p>
						<textarea class="form-control" rows="3" id="modal-data-comments" name="comments"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger" name="checkoff" value="0" title="No Checkoff">No Checkoff</button>
					<button type="submit" class="btn btn-success" name="checkoff" value="1" id="checkoff-confirm" onclick="return ($('#modal-data-user').val()!=0  || confirm('Are you sure? Giving checkoff to duty with no user.'));">Confirm Checkoff</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php echo foot(); ?>