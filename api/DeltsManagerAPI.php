<?php

/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/24/16
 * Time: 5:00 PM
 */

require_once 'APIFramework.php';

class DeltsManagerAPI extends APIFramework
{
    /**
     * @var \Models\User User to be used in request
     */
    protected $User;

    // TODO: delete in favor of one provided in db.php (and change references)
    /**
     * @var mysqli
     */
    protected $mysqli;

    // TODO: delete in favor of one provided in db.php
    /**
     * @var int success code
     */
    protected $success;


    /**
     * DeltsManagerAPI constructor.
     * @param $request string URI request
     * @param $origin string Origin of request
     * @throws Exception error
     */
    public function __construct($request, $origin) {
        parent::__construct($request);

        // TODO: delete when function
        define("USER_USER", 0);
        define("USER_CHECKER", 1);
        define("USER_HOUSE_MANAGER", 2);
        define("USER_HONOR_BOARD", 3);
        define("USER_ADMIN", 4);

        // get API Key factory
        // TODO: figure out whether or not i need this
        $APIKeyFactory = \Models\APIKeyFactory::Instance();

        // Check for API Key and User token errors
        // TODO: implement success codes
        if (!array_key_exists('apiKey', $this->request)) {
            throw new Exception("No API Key provided");
        } else if (!\Models\APIKeyFactory::verify_key($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        }


        // Token Validation and User Creation
        if (!array_key_exists('token', $this->request)) {
            throw new Exception("No user token provided");
        }

        $User = new Models\User();
        if (!$User->verify_token($this->request['token'])) {
            $email = $User->email_from_token($this->request['token']);
            $stmt = $this->mysqli->prepare("(SELECT id,email,first,CONCAT(first,' ',last) AS name FROM users WHERE email=?)");
            $stmt->bind_param("s", $email);
            $stmt->bind_result($res_id, $res_email, $res_first_name, $res_full_name);
        } else {
            throw new Exception('Invalid Token');
        }

        // Execution of User Creation
        if(isset($stmt)) {
            $stmt->execute();
            $stmt->fetch();
            $stmt->free_result();

            // Add to 'logins'
            $stmt2 = $this->mysqli->prepare("INSERT INTO logins(user,success) VALUES(?,?)");
            $stmt2->bind_param("ii",$res_id,$success);
            $stmt2->execute();

            // Populate User
            $User->user_id = $res_id;
            $User->user_first_name = $res_first_name;
            $User->user_full_name = $res_full_name;
            $User->user_email = $res_email;
            $res = $this->mysqli->query("SELECT role FROM roles WHERE user={$res_id};")->fetch_all(MYSQLI_NUM);
            foreach($res as $r) {
                // TODO: idk what this syntax is
                $User->user_privileges[] = $r[0];
            }
        }

        // Assign local to global
        $this->User = $User;
    }

    // API FUNCTIONS

    // ACCOUNT FUNCTIONS

    /**
     * Endpoint function for /account. Redirects according to verb /account/<verb>
     *
     * @throws Exception Verb not Found
     */
    protected function account() {
        switch ($this->verb) {
            case '':
                return $this->account_info();
            case 'duties':
                return $this->account_duties();
            case 'punts':
                return $this->account_punts();
            case 'checkoff':
                return $this->post_checkoff();
            default:
                throw new Exception('Verb Not Found');
        }
    }

    /**
     * Method for /account
     *
     * @return array Account info [id (int), name (string), email (string), privileges (array of strings)]
     */
    private function account_info() {
        $arr = array(
            "id" => $this->User->user_id,
            "first name" => $this->User->user_first_name,
            "full name" => $this->User->user_full_name,
            "email" => $this->User->user_email,
            "privileges" => $this->User->user_privileges
        );

        return $arr;
    }

    /**
     * Method for /account/duties [GET]
     *
     * @return mixed|array Array of house duties
     */
    private function account_duties() {
        // Taken from dashboard.php
        /*
        $sunday = strtotime("last Sunday 12:00am")-100;
        $limit = isset($_GET["all"])?"":" LIMIT 2";
        $duties_query = "(SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$this->User->user_id} AND checker <= 0 AND start > FROM_UNIXTIME({$sunday}) ORDER BY start ASC,dutyname ASC) UNION (SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$this->User->user_id} AND start > FROM_UNIXTIME({$sunday}) AND checker > 0 ORDER BY start DESC,dutyname ASC{$limit});";
        $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

        return $duties;
        */

        $duties_query = "((SELECT id, start, (SELECT title AS houseduty FROM housedutieslkp WHERE id = r.duty) FROM houseduties r WHERE user = {$this->User->user_id} AND checker <=0) ORDER BY start ASC, dutyname ASC) UNION ((SELECT id, start, (SELECT title AS houseduty FROM housedutieslkp WHERE id = r.duty) FROM houseduties r WHERE user = {$this->User->user_id} AND checker > 0) ORDER BY start ASC, dutyname ASC)";
        $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

        return $duties;
    }

    /**
     * Method for /account/punts [GET]
     *
     * @return mixed|array Array of punts
     */
    private function account_punts() {
        /*
        $punts_query = "(SELECT timestamp,comment,makeup_given_by,IF(p.given_by>0,(SELECT CONCAT(first,' ',last) FROM users WHERE id=p.given_by),'Delts Manager') AS givenname FROM punts p WHERE user={$this->User->user_id} ORDER BY timestamp DESC)";
        $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

        return $punts;
        */

        $punts_query = "SELECT id, IF(p.given_by > 0, (SELECT first FROM users WHERE id = p.given_by), 'Delts Manager'), comment, timestamp, makeup_timestamp, IF(makeup_given_by > 0, (SELECT first FROM users WHERE id = p.makeup_given_by), 'Delts Manager'), makeup_comment FROM punts p WHERE user = {$this->User->user_id} ORDER BY timestamp DESC";
        $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

        return $punts;
    }


    /**
     * Method for /account/checkoff [POST]
     * @return int
     * @throws Exception
     */
    private function post_checkoff() {
        $json_data = json_decode($this->file);

        if(array_key_exists("DutyID", $json_data)) {
            $duty_id = $this->mysqli->real_escape_string($json_data["DutyID"]);
        } else {
            throw new Exception("Duty ID not found");
        }

        // checker = -1 is requested checkoff, checker = 0 is no checkoff, any other is user id of checker
        $res = $this->mysqli->prepare("UPDATE houseduties SET checker=-1,checktime=CURRENT_TIMESTAMP WHERE id=? AND checker=0 AND user=?");
        $res->bind_param("ii",$duty_id, $this->User->user_id);
        $res->execute();

        // TODO: Implement status codes
        if($res->affected_rows > 0) {
            send_email($this->User->user_email,"Checkoff Requested","{$this->User->user_full_name},\r\n\r\nYou just requested a checkoff for a duty.\r\n\r\nCheers,\r\nDM");
            send_email("dtd-checkers@mit.edu","Checkoff Requested","Checkers,\r\n\r\n{$this->User->user_full_name} just requested a checkoff. Visit http://".BASE_URL."/checker_dashboard.php to give them a checkoff.\r\n\r\nCheers,\r\nDM");

            return 1;
        } else {

            return 0;
        }
    }


    // SCHEDULING FUNCTIONS
    protected function house_duty_names() {
        switch ($this->verb) {
            case '':
                break;
            default:
                throw new Exception("Verb Not Found");
        }

        //$houseduties = $this->mysqli->query("SELECT id,title,description FROM housedutieslkp;")->fetch_all(MYSQLI_ASSOC);

        /*$dutynames = [];
        foreach($houseduties as $h) {
            $dutynames[$h["id"]] = $h;
        }
        */

        $houseduties = $this->mysqli->query("SELECT title FROM housedutieslkp;")->fetch_all(MYSQLI_ASSOC);

        return $houseduties;
    }

    // MANAGER FUNCTIONS
    protected function manager() {
        switch ($this->verb) {
            case 'duties':
                return $this->manager_duties();
            case 'punts':
                return $this->manager_punts();
            case 'duty_checkoffs':
                return $this->requested_checkoffs();
            case 'checkoff_duty':
                return $this->checkoff_duty();
            case 'punt':
                return $this->punt();
            default:
                throw new Exception("Verb Not Found");
        }
    }

    // all duties (duties -> admin tab)
    private function manager_duties() {
        if(user_authorized([USER_HOUSE_MANAGER])) {
            $duties_query = "SELECT id, (SELECT CONCAT(first, ' ', last) FROM users WHERE id = d.user), (SELECT title AS houseduty FROM housedutieslkp WHERE id = d.duty), start, checker, checktime, checkcomments FROM houseduties d";
            $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

            return $duties;

        } else {
            throw new Exception("User Not Authorized");
        }
    }

    // all punts (punts -> admin tab)
    private function manager_punts() {
        if (user_authorized([USER_HOUSE_MANAGER, USER_HONOR_BOARD])) {
            $punts_query = "SELECT id, (SELECT CONCAT(first, ' ', last) FROM users WHERE id = p.user), IF(p.given_by > 0, (SELECT first FROM users WHERE id = p.given_by), 'Delts Manager'), timestamp, comment, IF(makeup_given_by > 0, (SELECT first FROM users WHERE id = p.makeup_given_by), 'Delts Manager'), makeup_timestamp, makeup_comment FROM punts p ORDER BY timestamp DESC";
            $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

            return $punts;
        } else {
            throw new Exception("User Not Authorized");
        }

    }

    // req checkoffs (duties -> checkoff)
    private function requested_checkoffs() {
        if(user_authorized([USER_CHECKER, USER_HOUSE_MANAGER])) {
            $checkoffs_query = "SELECT id,(SELECT CONCAT(first,' ',last) FROM users WHERE id=r.user), (SELECT title FROM housedutieslkp WHERE id = r.duty), start FROM houseduties r WHERE checker=-1;";
            $checkoffs = $this->mysqli->query($checkoffs_query)->fetch_all(MYSQLI_ASSOC);

            return $checkoffs;
        } else {
            throw new Exception("User Not Authorized");
        }
    }

    // checker grant checkoff
    private function checkoff_duty() {
        $json_data = json_decode($this->file);

        if(array_key_exists("Comments", $json_data)) {
            $comments = $this->mysqli->real_escape_string($json_data["Comments"]);
        } else {
            throw new Exception("Comments not found");
        }

        if(array_key_exists("DutyID", $json_data)) {
            $duty_id = $this->mysqli->real_escape_string($json_data["DutyID"]);
        } else {
            throw new Exception("Duty ID not found");
        }

        if(array_key_exists("UserID", $json_data)) {
            $user = $this->mysqli->real_escape_string($json_data["UserID"]);
        } else {
            throw new Exception("User ID not found");
        }

        $checker = $this->User->user_id; // TODO: implement for real (send from app)

        $stmt = $this->mysqli->prepare("UPDATE houseduties SET checktime=CURRENT_TIMESTAMP,checkcomments=?,user=?,checker={$checker} WHERE id=?");
        $stmt->bind_param("sii",$comments,$user,$duty_id);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            return 0;
        }

        $stmt = $this->mysqli->prepare("SELECT email FROM users WHERE id=(SELECT user FROM houseduties WHERE id=?)");
        $stmt->bind_param("i",$duty_id);
        $stmt->bind_result($user_email);
        $stmt->execute();
        $stmt->fetch();
        $stmt->free_result();

        if (!($stmt->affected_rows > 0)) {
            return 1;
        }

        $checker_name = $this->User->user_first_name;

        $message = "{$user},\r\n\r\n{$checker_name} just checked off one of your duties.\r\n\r\nCheers,\r\n\tDM";
        $subject = "Checked Off";

        send_email($user_email,$subject,$message);

        return 2;
    }

    // give new punt (Punt -> plus)
    private function punt() {
        $user = $this->User->user_id;
        $user_to_be_punted = 0; // TODO: implement for real (Send from app)
        $comment = ""; // TODO: implement for real (Send from app)

        $stmt = $this->mysqli->prepare("INSERT INTO punts(user,given_by,comment) VALUES(?,?,?)");
        $stmt->bind_param("iis",$user_to_be_punted, $user, $comment);

        if($stmt->execute()) {
            return 1;
        } else {
            return 0; // DB error
        }
    }
}