<?php
require_once("includes/imports.php");
user_check_authorized(0);

$days = [1=>"Mon",3=>"Wed",4=>"Thu",6=>"Sat"];

$weekstart = strtotime(isset($_GET["week"])?$_GET["week"]:"Sunday 12:00:00am");
$weekstart -= date('N',$weekstart)*24*60*60;
$weekfinish = $weekstart + 7*24*60*60-1;
// $weekfinish = strtotime("Saturday 11:59:59pm");
// $weekstart = $weekfinish - 7*24*60*60+1;

$l = date('Y-m-d',$weekstart);
$h = date('Y-m-d',$weekfinish);

$modify = time()<$weekfinish || (time() < $weekstart+12*60*60 && time() > $weekstart-36*60*60);


if(isset($_POST["claim"])) {
	if($modify) {
		$stmt = $mysqli->prepare("UPDATE houseduties SET user=? WHERE id=? AND user=0 AND start >= '{$l}' AND start <= '{$h}' AND checker=0");
		$stmt->bind_param("ii",$_SESSION["user_id"],$_POST["claim"]);
		$stmt->execute();
		if($stmt->affected_rows > 0) {
			$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Duty Claimed</div>";
		} else {
			$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> Duty already claimed by another user (or invalid duty)</div>";
		}
	} else {
		$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> You may only modify duties from Friday at noon to Sunday at noon.</div>";
	}
}
if(isset($_POST["disclaim"])) {
	if($modify) {
		$stmt = $mysqli->prepare("UPDATE houseduties SET user=0 WHERE id=? AND user=?");
		$stmt->bind_param("ii",$_POST["disclaim"],$_SESSION["user_id"]);
		$stmt->execute();
		if($stmt->affected_rows > 0) {
			$msg = "<div class=\"alert alert-success\"><strong>Success!</strong> Duty Disclaimed</div>";
		} else {
			$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> You do not have this duty to disclaim</div>";
		}
	} else {
		$msg = "<div class=\"alert alert-danger\"><strong>Error!</strong> You may only modify duties from Friday at noon to Sunday at noon.</div>";
	}
}
$temp = $mysqli->query("SELECT id,title,description FROM housedutieslkp;")->fetch_all(MYSQLI_ASSOC);
$dutynames = [];
foreach($temp as $t) {
	$dutynames[$t["id"]] = $t;
}


$duties = $mysqli->query("SELECT id,(DAYOFWEEK(start)-1) AS startday,user,IF(r.user=0,'Available',(SELECT CONCAT(first,' ',last) FROM users WHERE id=r.user)) AS username,duty FROM houseduties r WHERE start>='{$l}' AND start<='{$h}' ORDER BY start ASC,id ASC;");
$duties = $duties->fetch_all(MYSQLI_ASSOC);

$data = [];
$lines = [];
$duties_by_day = [];
$days_by_duty = [];
$dutylkp = [];
foreach($duties as $d) {
	$day = $d["startday"];

	if(!isset($duties_by_day[$day])) $duties_by_day[$day] = [];
	$duties_by_day[$day][$d["duty"]] = isset($duties_by_day[$day][$d["duty"]])?$duties_by_day[$day][$d["duty"]]+1:1;

	if(!isset($days_by_duty[$d["duty"]])) $days_by_duty[$d["duty"]] = [];
	$days_by_duty[$d["duty"]][]=["day"=>$day,"id"=>$d["id"]];

	$dutylkp[$d["id"]] = $d;
}
$max_duties = [];
foreach($duties_by_day as $d) {
	foreach($d as $key=>$val) {
		$max_duties[$key] = isset($max_duties[$key])?max($max_duties[$key],$val):$val;
	}
}
$rows = [];
foreach($max_duties as $key=>$val) {
	for($i=0;$i<$val;$i++) {
		$d = [];
		foreach($days_by_duty[$key] as $k=>$v) {
			if(!in_array($v["day"],$d)) {
				$d[] = $v;
				unset($days_by_duty[$key][$k]);
			}
		}
		$rows[] = ["title"=>$dutynames[$key]["title"],"description"=>$dutynames[$key]["description"],"days"=>$d];
	}
}


// $data = [];
// $heads = [];
// foreach($duties as $d) {
// 	if(!isset($data[$d["startday"]])) $data[$d["startday"]] = [];

// 	$found = false;

// 	foreach($heads as $key=>$val) {
// 		if($val == $d["duty"] && !isset($data[$d["startday"]][$key])) {
// 			$data[$d["startday"]][$key] = $d;
// 			$found = true;
// 			break;
// 		}
// 	}
// 	if(!$found) {
// 		$num = count($heads);
// 		$heads[$num] = ["title"=>$d["duty"],"days"=>[$d["startday"]=>$d["id"]]];
// 		$data[$d["startday"]][$num] = $d;
// 	}
// }
// $data = [];
// foreach($duties as $d) {
// 	$data[$d["id"]] = $d;
// }
echo head1("House Duties");
?>
<script>
$(document).ready(function() {
	$('[data-toggle="tooltip"]').tooltip({html:true});
});
</script>
<style>
@media(min-width: 768px) {
	.housedutyday {
		width:80px;
	}
}
@media (max-width: 767px) {
	.housedutyday {
		width:40px;
	}
}

</style>
<?php
echo head2();
?>
<div class="row">
	<div class="col-xs-12">
		<div class="page-header"><h1>House Duties <small><?php echo date('D m/d/Y',$weekstart). "-" . date("D m/d/Y",$weekfinish); ?></small></h1></div>
		<?php
		if(isset($msg)) echo $msg; ?>
		<div class="row">
			<form action="houseduties.php?week=<?php echo urlencode($h); ?>" method="post">
				<table class="table table-bordered">
					<thead>
						<tr>
							<td></td>
							<?php
							foreach($days as $key=>$value) {
								echo "<th class=\"housedutyday\">{$value}</th>";
							}
							?>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($rows as $row) {
						$tds = "";
						$jdays = [];
						foreach($row["days"] as $d) {
							$jdays[$d["day"]] = $dutylkp[$d["id"]];
						}

						foreach($days as $d=>$s) {
							if(isset($jdays[$d])) {
								$r = $jdays[$d];
								$btntype = "default";
								if($r["user"]) {
									if($r["user"]==user_id()) {
										$disabled = $modify?["submit",""]:["button"," disabled"];
										$name = $modify?"disclaim":"";
										$u = $modify?"<strong>Disclaim</strong>":user_name();
										$btntype = "success";
									} else {
										$disabled = ["button"," disabled"];
										$name = "";
										$u = $r["username"];
										$btntype = "danger";
									}
								} else {
									$disabled = $modify?["submit",""]:["button"," disabled"];
									$u = $modify?"Claim":"<strong>Nobody</strong>";
									$name = $modify?"claim":"";
								}
								$tds .= "<td><button type=\"{$disabled[0]}\" class=\"btn btn-{$btntype} col-xs-12 btn-lg{$disabled[1]}\" data-toggle=\"tooltip\" title=\"{$u}\" name=\"{$name}\" value=\"{$r["id"]}\"></button></td>";
							} else {
								$tds .= "<td class=\"well\"></td>";
							}
						}
						echo "<tr><td><div title=\"{$row["description"]}\" data-toggle=\"tooltip\" style=\"width:100%\">{$row["title"]}</div></td>{$tds}</tr>";
					}
					// foreach($heads as $value) {
					// 	$tds = "";
					// 	foreach($days as $d) {
					// 		if(isset($value["days"][$d])) {
					// 			$row = $data[$value["days"][$d]];
					// 			$btntype = "default";
					// 			if($row["user"]) {
					// 				if($row["user"]==user_id()) {
					// 					$disabled = $modify?["submit",""]:["button"," disabled"];
					// 					$name = $modify?"disclaim":"";
					// 					$u = $modify?"<strong>Disclaim</strong>":user_name();
					// 					$btntype = "success";
					// 				} else {
					// 					$disabled = ["button"," disabled"];
					// 					$name = "";
					// 					$u = $row["username"];
					// 					$btntype = "danger";
					// 				}
					// 			} else {
					// 				$disabled = $modify?["submit",""]:["button"," disabled"];
					// 				$u = $modify?"Claim":"<strong>Nobody</strong>";
					// 				$name = $modify?"claim":"";
					// 			}

					// 			$tds .= "<td><button type=\"{$disabled[0]}\" class=\"btn btn-{$btntype} col-xs-12 btn-lg{$disabled[1]}\" data-toggle=\"tooltip\" title=\"{$u}\" name=\"{$name}\" value=\"{$row["id"]}\"></button></td>";
					// 		} else {
					// 			$tds .= "<td class=\"well\"></td>";
					// 		}
					// 	}
					// 	echo "<tr><td><div title=\"{$dutynames[$value["title"]]["description"]}\" data-toggle=\"tooltip\" style=\"width:100%\">{$dutynames[$value["title"]]["title"]}</div></td>{$tds}</tr>";
					// }
					/*foreach($heads as $key=>$value) {
						$tds = "";
						foreach($data as $d) {
							if(isset($d[$key])) {
								$row = $d[$key];
								$btntype = "default";
								if($row["user"]) {
									if($row["user"]==user_id()) {
										$disabled = $modify?["submit",""]:["button"," disabled"];
										$name = $modify?"disclaim":"";
										$u = $modify?"<strong>Disclaim</strong>":user_name();
										$btntype = "success";
									} else {
										$disabled = ["button"," disabled"];
										$name = "";
										$u = $row["username"];
										$btntype = "danger";
									}
								} else {
									$disabled = $modify?["submit",""]:["button"," disabled"];
									$u = $modify?"Claim":"<strong>Nobody</strong>";
									$name = $modify?"claim":"";
								}

								$tds .= "<td><button type=\"{$disabled[0]}\" class=\"btn btn-{$btntype} col-xs-12 btn-lg{$disabled[1]}\" data-toggle=\"tooltip\" title=\"{$u}\" name=\"{$name}\" value=\"{$row["id"]}\"></button></td>";
							} else {
								$tds .= "<td class=\"well\"></td>";
							}
						}
						echo "<tr><td><div title=\"{$dutynames[$value]["description"]}\" data-toggle=\"tooltip\" style=\"width:100%\">{$dutynames[$value]["title"]}</div></td>{$tds}</tr>";
					}*/
					?>
					</tbody>
				</table>
			</form>
		</div>
	</div>
</div>
<?php echo foot(); ?>
