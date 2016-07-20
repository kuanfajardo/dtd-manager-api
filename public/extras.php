<?php
require_once("includes/imports.php");

$brotheremail = "dtd-summer-brothers@mit.edu";

if(isset($_POST["pw"]) && isset($_POST["req"]) && $_POST["pw"]==="tgjEUiuNintJuWuBn19N9QEMIPgOYQl") {
	if($_POST["req"]=="autoassign") {

		$weekfinish = strtotime("Saturday 11:59:59pm");
		$weekstart = $weekfinish - 7*24*60*60+1;

		$ll = date('Y-m-d',$weekstart-3*7*24*60*60);
		$l = date('Y-m-d',$weekstart);
		$h = date('Y-m-d',$weekfinish);

		$duties = $mysqli->query("SELECT houseduties.id,housedutieslkp.title,houseduties.start FROM houseduties LEFT JOIN housedutieslkp ON houseduties.duty=housedutieslkp.id WHERE houseduties.start >= '{$l}' AND houseduties.start <= '{$h}' AND houseduties.user=0 ORDER BY houseduties.start ASC, houseduties.duty ASC")->fetch_all(MYSQLI_ASSOC);

		if(count($duties)==0) exit;

		$query = "SELECT id,first,last,email,(SELECT COUNT(*) FROM houseduties WHERE user=users.id AND checker>0 AND start>='{$ll}' AND start <= '{$l}')/duties AS score,duties-(SELECT COUNT(*) FROM houseduties WHERE user=users.id AND start>='{$l}') AS lacking,duties FROM users WHERE duties > 0 ORDER BY lacking DESC,score ASC";
		$users = $mysqli->query($query);
		echo $mysqli->error;
		$users = $users->fetch_all(MYSQLI_ASSOC);


		// printit($duties);
		// printit($users);
		// die();
		$currcount = 0;
		$userindex = 0;
		$user = $users[$userindex];

		$stmt = $mysqli->prepare("UPDATE houseduties SET user=? WHERE id=?");
		$stmt->bind_param("ii",$userid,$dutyid);

		foreach($duties as $row) {
			if($currcount < $user["duties"]) {
				$currcount++;
			} else {
				$userindex++;
				if($userindex > count($users)) {
					error_email([USER_ADMIN,USER_HOUSE_MANAGER],"Not enough users to automatically assign duties. Please manually assign them.");
					break;
				}
				$user = $users[$userindex];

				$currcount = 1;
			}

			$dutyid = $row["id"];
			$userid = $user["id"];
			//echo $user["first"] . " " . $user["last"] . ": " . $row["title"] . "<br/>";
			$stmt->execute();
		}
		send_email($brotheremail,"Duty Sheet Complete","Duty sheet is now closed for the week.\r\n\r\nIf you forgot to sign up, you have been automatically assigned. Please check your dashboard at http://dtd.mit.edu/dashboard.php to see what duties you are responsible for completing.\r\n\r\nCheers,\r\n\tDM");
	} elseif($_POST["req"]=="autopunt") {
		$data = $mysqli->query("SELECT users.first,users.last,users.email,houseduties.user,housedutieslkp.title,houseduties.start FROM houseduties LEFT JOIN users ON users.id=houseduties.user LEFT JOIN housedutieslkp ON housedutieslkp.id=houseduties.duty WHERE houseduties.start=SUBDATE(CURRENT_DATE,1) AND houseduties.checker <= 0;")->fetch_all(MYSQLI_ASSOC);
		$mysqli->query("UPDATE houseduties SET checker=-10 WHERE checker <= 0 AND start=SUBDATE(CURRENT_DATE,1);");

		$stmt = $mysqli->prepare("INSERT INTO punts(user,given_by,comment) VALUES(?,0,?)");
		$stmt->bind_param("is",$user,$comment);
		foreach($data as $row) {
			$user = $row["user"];
			$date = date('m/d/Y',strtotime($row["start"]));
			$comment = "Automatic punt given by system to {$row["first"]} {$row["last"]} for their {$row["title"]} duty, which was never checked off on {$row["start"]}";
			$email = "Dear {$name},\r\n\r\nYou just received a punt:\r\n\r\nGiven By: Delts Manager\r\nComment: {$comment}\r\n\r\nPlease contact the House Manager for punt makeup opportunites.\r\n\r\nCheers,\r\n\tDM";
			$stmt->execute();
			send_email($row["email"],"Punt!",$email);
		}
	} elseif($_POST["req"]=="emailreminders") {
		$today = date('Y-m-d');
		//get duties that need to be done today
		$data = $mysqli->query("SELECT CONCAT(users.first,' ',users.last) AS username,users.email,housedutieslkp.title,housedutieslkp.description FROM houseduties LEFT JOIN users ON houseduties.user=users.id LEFT JOIN housedutieslkp ON housedutieslkp.id=houseduties.duty WHERE houseduties.start='{$today}';")->fetch_all(MYSQLI_ASSOC);

		foreach($data as $row) {
			send_email($row["email"],"Duty Reminder","Dear {$row["username"]},\r\n\r\nYou have a duty today. Remember to get it checked off in the system, or you will be assigned a punt.\r\n\r\nDuty: {$row["title"]}\r\nDescription: {$row["description"]}\r\n\r\nCheers,\r\n\tDM");
		}
	} elseif($_POST["req"]=="emailremindersnag") {
		$today = date('Y-m-d');
		//get duties that need to be done today
		$data = $mysqli->query("SELECT CONCAT(users.first,' ',users.last) AS username,users.email,housedutieslkp.title,housedutieslkp.description FROM houseduties LEFT JOIN users ON houseduties.user=users.id LEFT JOIN housedutieslkp ON housedutieslkp.id=houseduties.duty WHERE houseduties.start='{$today}' AND checker <= 0;")->fetch_all(MYSQLI_ASSOC);

		foreach($data as $row) {
			send_email($row["email"],"Do Your Duty","Dear {$row["username"]},\r\n\r\nRemember to get your duty done and checked off! Automated punts are going out at 2am, and you still have not had your duty checked off in the system.\r\n\r\nDuty:{$row["title"]}\r\nDescription:{$row["description"]}\r\n\r\nCheers,\r\n\tDM");
		}
	} elseif($_POST["req"]=="dutysheet") {
		send_email($brotheremail,"Duty Sheet Live","Delts,\r\n\r\nDuty sheet is how live at http://dtd.mit.edu/houseduties.php. Remember to sign up for your duties by noon on Sunday, or you will be responsible for completing automatically assigned duties this week.\r\n\r\nCheers,\r\n\tDM");
	} elseif($_POST["req"]=="dutysheetnag") {
		send_email($brotheremail,"Duty Sheet Closing Soon","Delts,\r\n\r\nDuty sheet is live at http://dtd.mit.edu/houseduties.php. Duties close in two hours (every Sunday at noon). Remember to sign up, or you will be responsible for completing automatically assigned duties this week.\r\n\r\nCheers,\r\n\tDM");
	} else {
		echo "Test success";
	}
} else {
	redirect_to("index.php");
}
?>
