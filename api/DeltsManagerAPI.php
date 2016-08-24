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

    ////////////////////
    //// PROPERTIES ////
    ////////////////////

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


    /////////////////////
    //// CONSTRUCTOR ////
    /////////////////////

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

        if (!$this->endpoint == 'authenticate') {
            $json_data = json_decode($this->file);

            // Check for API Key and User token errors
            if (!array_key_exists('api_key', $json_data)) {
                throw new Exception("No API Key provided");
            } else if (!\Models\APIKeyFactory::verify_key($json_data['api_key'], $origin)) {
                throw new Exception('Invalid API Key');
            }

            // Token Validation and User Creation
            if (!array_key_exists('token', $json_data)) {
                throw new Exception("No user token provided");
            }

            $User = new Models\User();

            if (!$User->verify_token($json_data['token'])) {
                throw new Exception('Invalid Token');
            }

            // Email Validation and User Population
            if (!array_key_exists('email', $json_data)) {
                throw new Exception("No Email provided");
            }

            //$email = $User->email_from_token($this->request['token']);
            $email = $this->$json_data['email'];
            $stmt = $this->mysqli->prepare("SELECT id,email,first,CONCAT(first,' ',last) AS name FROM users WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->bind_result($res_id, $res_email, $res_first_name, $res_full_name);

            // Execution of User Creation
            if (isset($stmt)) {
                $stmt->execute();
                $stmt->fetch();
                $stmt->free_result();

                /*
                // Add to 'logins'
                $stmt2 = $this->mysqli->prepare("INSERT INTO logins(user,success) VALUES(?,?)");
                $stmt2->bind_param("ii", $res_id, $success);
                $stmt2->execute();
                */

                // Populate User
                $User->user_id = $res_id;
                $User->user_first_name = $res_first_name;
                $User->user_full_name = $res_full_name;
                $User->user_email = $res_email;
                $res = $this->mysqli->query("SELECT role FROM roles WHERE user={$res_id};")->fetch_all(MYSQLI_NUM);
                foreach ($res as $r) {
                    // TODO: idk what this syntax is
                    $User->user_privileges[] = $r[0];
                }
            }

            // Assign local to global
            $this->User = $User;
        }
    }


    ///////////////////////
    //// API FUNCTIONS ////
    ///////////////////////

    //--------------------
    // ACCOUNT FUNCTIONS |
    //--------------------

    /**
     * Endpoint function for /account. Redirects according to verb /account/<verb>
     * @return array
     * @throws Exception VerbNotFound
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
            case 'stats':
                return $this->user_stats();
            default:
                throw new Exception('Verb Not Found');
        }
    }

    /**
     * Method for /account [GET]
     *
     * @return array Account info [id (int), name (string), email (string), privileges (array of strings)]
     */
    private function account_info() {
        $arr = array(
            "user_id" => $this->User->user_id,
            "first_name" => $this->User->user_first_name,
            "full_name" => $this->User->user_full_name,
            "email" => $this->User->user_email,
            "privileges" => $this->User->user_privileges
        );

        return $arr;
    }

    /**
     * Method for /account/duties [GET]
     *
     * @return array Array of house duties
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

        //$duties_query = "SELECT id AS duty_id, start AS date, (SELECT title AS duty_name FROM housedutieslkp WHERE id = r.duty), (SELECT description FROM housedutieslkp WHERE id = r.duty) FROM houseduties r WHERE user = {$this->User->user_id} ORDER BY date ASC, duty_name ASC";
        $duties_query = "SELECT id AS duty_id, start AS date, (SELECT title AS duty_name FROM housedutieslkp WHERE id = r.duty), (SELECT description FROM housedutieslkp WHERE id = r.duty), CASE r.checker WHEN -1 THEN 'Pending' WHEN 0 THEN 'Incomplete' ELSE 'Complete' END AS status, IF(r.checker > 0, (SELECT first FROM users WHERE id = r.checker), 'N/A') AS checker, checkcomments AS check_comments FROM houseduties r";
        $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

        return $duties;
    }

    /**
     * Method for /account/punts [GET]
     *
     * @return array Array of punts
     */
    private function account_punts() {
        /*
        $punts_query = "(SELECT timestamp,comment,makeup_given_by,IF(p.given_by>0,(SELECT CONCAT(first,' ',last) FROM users WHERE id=p.given_by),'Delts Manager') AS givenname FROM punts p WHERE user={$this->User->user_id} ORDER BY timestamp DESC)";
        $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

        return $punts;
        */

        $punts_query = "SELECT id AS punt_id, IF(p.given_by > 0, (SELECT first FROM users WHERE id = p.given_by), 'Delts Manager') AS given_by, comment, timestamp, makeup_timestamp, IF(p.makeup_given_by > 0, (SELECT first FROM users WHERE id = p.makeup_given_by), '---') AS makeup_given_by, makeup_comment FROM punts p WHERE user = {$this->User->user_id} ORDER BY timestamp DESC";
        $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

        return $punts;
    }

    /**
     * Method for /account/checkoff [POST]
     *
     * @return array Status array
     * @throws Exception KeyError
     */
    private function post_checkoff() {
        $json_data = json_decode($this->file);

        if(array_key_exists("duty_id", $json_data)) {
            $duty_id = $this->mysqli->real_escape_string($json_data["duty_id"]);
        } else {
            throw new Exception("Duty ID not found");
        }

        // checker = -1 is requested checkoff, checker = 0 is no checkoff, any other is user id of checker
        $res = $this->mysqli->prepare("UPDATE houseduties SET checker=-1,checktime=CURRENT_TIMESTAMP WHERE id=? AND checker=0 AND user=?");
        $res->bind_param("ii",$duty_id, $this->User->user_id);
        $res->execute();

        if($res->affected_rows > 0) {
            send_email($this->User->user_email,"Checkoff Requested","{$this->User->user_full_name},\r\n\r\nYou just requested a checkoff for a duty.\r\n\r\nCheers,\r\nDM");
            send_email("dtd-checkers@mit.edu","Checkoff Requested","Checkers,\r\n\r\n{$this->User->user_full_name} just requested a checkoff. Visit http://".BASE_URL."/checker_dashboard.php to give them a checkoff.\r\n\r\nCheers,\r\nDM");

            return array(
                'status' => 1
            );
        } else {
            throw new Exception("DB Error: not updated");
            /*
            return array(
                'status' => 0
            );
            */
        }
    }

    /**
     * Method for /account/stats [GET]
     *
     * @return array Array with stts needed for overview vc -
     * number of duties, punts, and whether house duty schedule is open
     */
    private function user_stats() {
        $duties = $this->account_duties();
        $num_duties = count($duties);

        $punts = $this->account_punts();
        $num_punts = count($punts);

        // Check for duty window
        $weekstart = strtotime(isset($_GET["week"])?$_GET["week"]:"Sunday 12:00:00am");
        $weekstart -= date('N',$weekstart)*24*60*60;
        $weekfinish = $weekstart + 7*24*60*60-1;

        $modify = time()<$weekfinish || (time() < $weekstart+12*60*60 && time() > $weekstart-36*60*60);

        return array(
            'num_duties' => $num_duties,
            'num_punts' => $num_punts,
            'schedule_open' => $modify
        );
    }

    //-----------------------
    // SCHEDULING FUNCTIONS |
    //-----------------------

    /**
     * Endpoint function for /schedulingRedirects according to verb /scheduling/<verb>
     *
     * @return array
     * @throws Exception VerbNotFound
     */
    protected function scheduling() {
        switch ($this->verb) {
            case 'house_duties':
                return $this->house_duties();
            case 'house_duty_names':
                return $this->house_duty_names();
            case 'update_duty':
                return $this->update_duty();
            default:
                throw new Exception("Verb Not Found");
        }
    }

    /**
     * Method for scheduling/house_duties [GET]
     *
     * @return array Array of ALL duties for the current week
     */
    protected function house_duties() {

        $weekstart = strtotime(isset($_GET["week"])?$_GET["week"]:"Sunday 12:00:00am");
        $weekstart -= date('N',$weekstart)*24*60*60;
        $weekfinish = $weekstart + 7*24*60*60-1;

        $l = date('Y-m-d',$weekstart);
        $h = date('Y-m-d',$weekfinish);

        $duties_query ="SELECT id as duty_id,(DAYOFWEEK(start)-1) AS day_of_week, IF(r.user=0,'Available',(SELECT first FROM users WHERE id=r.user)) AS user_name, (SELECT title AS duty_name FROM housedutieslkp WHERE id = r.duty) FROM houseduties r WHERE start>='{$l}' AND start<='{$h}' ORDER BY start ASC,id ASC;";
        $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

        return $duties;
    }

    /**
     * Method for /scheduling/house_duty_names [GET]
     *
     * @return array Array of house duties
     */
    private function house_duty_names() {
        //$houseduties = $this->mysqli->query("SELECT id,title,description FROM housedutieslkp;")->fetch_all(MYSQLI_ASSOC);

        /*$dutynames = [];
        foreach($houseduties as $h) {
            $dutynames[$h["id"]] = $h;
        }
        */

        $houseduties_query = "SELECT title AS duty_title FROM housedutieslkp";
        $houseduties = $this->mysqli->query($houseduties_query)->fetch_all(MYSQLI_ASSOC);

        return $houseduties;
    }

    /**
     * Method for /scheduling/update_duty [POST]
     *
     * @return array Status array
     * @throws Exception KeyError, DBError, UIError
     */
    private function update_duty() {
        // parse input data
        $json_data = json_decode($this->file);

        if (array_key_exists('duty_id', $json_data)) {
            $duty_id = $json_data['duty_id'];
        } else {
            throw new Exception("No duty id");
        }

        if (array_key_exists('claim', $json_data)) {
            $claim = $json_data['claim'];
        } else {
            throw new Exception("No claim data provided");
        }

        // Check for duty window
        $weekstart = strtotime(isset($_GET["week"])?$_GET["week"]:"Sunday 12:00:00am");
        $weekstart -= date('N',$weekstart)*24*60*60;
        $weekfinish = $weekstart + 7*24*60*60-1;

        $l = date('Y-m-d',$weekstart);
        $h = date('Y-m-d',$weekfinish);

        $modify = time()<$weekfinish || (time() < $weekstart+12*60*60 && time() > $weekstart-36*60*60);

        if (!$modify) {
            throw new Exception("Schedule not open");
        }

        // TODO: test whether to use 'true' or true
        switch ($claim) {
            case true:
                $stmt = $this->mysqli->prepare("UPDATE houseduties SET user=? WHERE id=? AND user=0 AND start >= '{$l}' AND start <= '{$h}' AND checker=0");
                $stmt->bind_param("ii",$this->User->user_id, $duty_id);
                $stmt->execute();
                if($stmt->affected_rows > 0) {
                    return array(
                        'status' => 1
                    );
                } else {
                    throw new Exception("Duty already taken");
                }
            case false:
                $stmt = $this->mysqli->prepare("UPDATE houseduties SET user=0 WHERE id=? AND user=?");
                $stmt->bind_param("ii",$duty_id,$this->User->user_id);
                $stmt->execute();
                if($stmt->affected_rows > 0) {
                    return array(
                        'status' => 1
                    );
                } else {
                    throw new Exception("You do not have this duty to disclaim");
                }
            default:
                throw new Exception("ONANA Your argument is invalid");
        }
    }


    //---------------------
    // MANAGER FUNCTIONS  |
    //---------------------

    /**
     * Endpoint method for /manager. Redirects according to verb /manager/<verb>
     *
     * @return array
     * @throws Exception VerbNotFound
     */
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
            case 'users':
                return $this->users();
            default:
                throw new Exception("Verb Not Found");
        }
    }

    /**
     * Method for /manager/duties [GET]
     *
     * @return array Array of all duties (ever)
     * @throws Exception UserNotAuthorized
     */
    private function manager_duties() {
        if(user_authorized([USER_HOUSE_MANAGER])) {
            $duties_query = "SELECT id AS duty_id, (SELECT CONCAT(first, ' ', last) AS duty_user_name FROM users WHERE id = d.user), (SELECT title AS duty_name FROM housedutieslkp WHERE id = d.duty), (SELECT description FROM housedutieslkp WHERE id = d.duty), start AS date,  CASE d.checker WHEN -1 THEN 'Pending' WHEN 0 THEN 'Incomplete' ELSE 'Complete' END AS status, IF(d.checker > 0, (SELECT first FROM users WHERE id = d.checker), 'N/A') AS checker, checkcomments AS check_comments FROM houseduties d";
            $duties = $this->mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

            return $duties;

        } else {
            throw new Exception("User Not Authorized");
        }
    }

    /**
     * Method for /manager/punts [GET]
     *
     * @return array Array of all punts (ever)
     * @throws Exception UserNotAuthorized
     */
    private function manager_punts() {
        if (user_authorized([USER_HOUSE_MANAGER, USER_HONOR_BOARD])) {
            $punts_query = "SELECT id AS punt_id, (SELECT CONCAT(first, ' ', last) AS punted_user_name FROM users WHERE id = p.user), IF(p.given_by > 0, (SELECT first FROM users WHERE id = p.given_by), 'Delts Manager') AS given_by, timestamp, comment, IF(makeup_given_by > 0, (SELECT first FROM users WHERE id = p.makeup_given_by), '---') AS makeup_given_by, makeup_timestamp, makeup_comment FROM punts p ORDER BY timestamp DESC";
            $punts = $this->mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

            return $punts;
        } else {
            throw new Exception("User Not Authorized");
        }

    }

    /**
     * Method for /manager/duty_checkoffs [GET]
     *
     * @return array Array of all requested checkoffs
     * @throws Exception UserNotAuthorized
     */
    private function requested_checkoffs() {
        if(user_authorized([USER_CHECKER, USER_HOUSE_MANAGER])) {
            $checkoffs_query = "SELECT id as duty_id,(SELECT CONCAT(first,' ',last) AS duty_user_name FROM users WHERE id=r.user), (SELECT title AS duty_name FROM housedutieslkp WHERE id = r.duty), (SELECT description FROM housedutieslkp WHERE id = r.duty), start AS date, 'Pending' AS status FROM houseduties r WHERE checker=-1;";
            $checkoffs = $this->mysqli->query($checkoffs_query)->fetch_all(MYSQLI_ASSOC);

            return $checkoffs;
        } else {
            throw new Exception("User Not Authorized");
        }
    }

    /**
     * Method for /manager/checkoff_duty [POST]
     *
     * @return array Status array
     * @throws Exception KeyError, DBError
     */
    private function checkoff_duty() {
        $json_data = json_decode($this->file);

        if(array_key_exists("comments", $json_data)) {
            $comments = $this->mysqli->real_escape_string($json_data["comments"]);
        } else {
            $comments = "";
        }

        if(array_key_exists("duty_id", $json_data)) {
            $duty_id = $this->mysqli->real_escape_string($json_data["duty_id"]);
        } else {
            throw new Exception("Duty ID not found");
        }

        /*
        if(array_key_exists("user_id", $json_data)) {
            $user = $this->mysqli->real_escape_string($json_data["user_id"]);
        } else {
            throw new Exception("User ID not found");
        }
        */

        $checker = $this->User->user_id;

        //$stmt = $this->mysqli->prepare("UPDATE houseduties SET checktime=CURRENT_TIMESTAMP,checkcomments=?,user=?,checker={$checker} WHERE id=?");
        $stmt = $this->mysqli->prepare("UPDATE houseduties SET checktime=CURRENT_TIMESTAMP,checkcomments=?,checker={$checker} WHERE id=?");
        $stmt->bind_param("sii",$comments,$duty_id);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            throw new Exception("DB error: failed to checkoff");
        }

        // added "first, and $user" to not delete {$user} in email thingy below
        $stmt = $this->mysqli->prepare("SELECT first, email FROM users WHERE id=(SELECT user FROM houseduties WHERE id=?)");
        $stmt->bind_param("i",$duty_id);
        $stmt->bind_result($user, $user_email);
        $stmt->execute();
        $stmt->fetch();
        $stmt->free_result();

        if (!($stmt->affected_rows > 0)) {
            return array(
                'status' => 2,
                'description' => 'Email not sent'
            );
        }

        $checker_name = $this->User->user_first_name;

        $message = "{$user},\r\n\r\n{$checker_name} just checked off one of your duties.\r\n\r\nCheers,\r\n\tDM";
        $subject = "Checked Off";

        send_email($user_email,$subject,$message);

        return array(
            'status' => 1,
            'description' => 'Email sent'
        );
    }

    /**
     * Method for /manager/punt [POST]
     *
     * @return array Status array
     * @throws Exception KeyNotFound, DBError
     */
    private function punt() {
        $json_data = json_decode($this->file);

        if(array_key_exists("punted_id", $json_data)) {
            $the_punted = $this->mysqli->$json_data["punted_id"];
        } else {
            throw new Exception("User to be punted not found");
        }

        if(array_key_exists("comments", $json_data)) {
            $comments = $this->mysqli->real_escape_string($json_data["comments"]);
        } else {
            $comments = "";
        }

        $user = $this->User->user_id;

        $success = true;

        foreach ($the_punted as $user_to_be_punted) {
            $stmt = $this->mysqli->prepare("INSERT INTO punts(user,given_by,comment) VALUES(?,?,?)");
            $stmt->bind_param("iis", $user_to_be_punted, $user, $comments);
            $stmt->execute();

            if (!$stmt->affected_rows > 0) {
                $success = false;
            }
        }

        if ($success) {
            return array(
                'status' => 1
            );
        } else {
            throw new Exception("DB error: at least one error");
        }

    }

    /**
    * Method for /manager/users [GET]
    *
    * @return array Array of all delt names
    */
    private function users() {
        $users_query = "SELECT CONCAT(first, ' ', last) AS user_name FROM users";
        $users = $this->mysqli->query($users_query)->fetch_all(MYSQLI_ASSOC);

        return $users;
    }



    //--------------------------
    // AUTHENTICATE FUNCTIONS  |
    //--------------------------

    /**
     * Method for /authenticate [POST]
     *
     * (A) Checks for digest auth keys in HTTP Body -> if not found, respond with necessary keys to client
     * (B) If found, (which means client either (1) has gone through step A or (2) has not, and is trying to hack), then
     *      compute server side login hashes and compare, then return success array accordingly
     *
     * @return array (A) Digest Auth keys array OR (B) Status array
     * @throws Exception AuthError
     */
    protected function authenticate() {
        $json_data = json_decode($this->file);

        // Check for API Key
        if (!array_key_exists('api_key', $json_data)) {
            throw new Exception("No API Key provided");
        } else if (!\Models\APIKeyFactory::verify_key($json_data['api_key'], "")) {
            throw new Exception('Invalid API Key');
        }

        $digest_keys = ['username', 'realm', 'nonce', 'opaque', 'method', 'uri', 'response'];

        $realm = md5('dtd');
        $nonce = md5($this->unique_string(32));
        $opaque = md5($this->unique_string(32));

        // PART A
        foreach ($digest_keys as $key) {
            if (!array_key_exists($key, $json_data)) {
                return array(
                    'realm' => $realm,
                    'nonce' => $nonce,
                    'opaque' => $opaque
                );
            }
        }

        // PART B
        $username = $json_data['username'];
        $response = $json_data['response'];
        $method = $json_data['method'];
        $uri = $json_data['uri'];

        $password_query = "SELECT password from users WHERE email = {$json_data['email']}";
        $password = $this->mysqli->query($password_query)->fetch_all(MYSQLI_ASSOC);


        $a1 = md5("{$username}:{$realm}:{$password}");
        $a2 = md5("{$method}:{$uri}");
        $auth = md5("{$a1}:{$nonce}:{$a2}");

        if ($response == $auth) {
            return array(
                'status' => 1
            );
        } else {
            return array(
                'status' => 0
            );
        }
    }


    //-------------------
    // HELPER FUNCTIONS |
    //-------------------

    private function unique_string($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    // TODO: Maybe use?
    /*
    private function api_status_description_from_code($code) {
        switch ($code) {
            case 0:
                return 'General Error';
            case 1:
                return 'Success';
            case 2:
                return 'Success - Email Failure';
            case 3:
                return 'Authorization Error';
            case 4:
                return 'Database UPDATE Error';
            case 5:
                return 'Database GET Error';
            case 6:
                return 'Key Error';
            case 7:
                return 'URI error';
            default:
                return 'Unknown Error';
        }
    }
    */
}