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

    // TODO: delete in favor of one provided in db.php
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

        // get API Key factory
        // TODO: figure out whether or not i need this
        $APIKeyFactory = \Models\APIKeyFactory::Instance();

        // Check for API Key and User token errors
        // TODO: implement success codes
        if (!array_key_exists('apiKey', $this->request)) {
            throw new Exception("No API Key provided");
        } else if (!\Models\APIKeyFactory::verify_key($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        } else if (!array_key_exists('token', $this->request)) {
            throw new Exception("No User Token Provided");
        }


        // Token Validation and User Creation
        $User = new Models\User();
        if (!$User->verify_token($this->request['token'])) {
            $email = $User->email_from_token($this->request['token']);
            $stmt = $mysqli->prepare("SELECT id,email,CONCAT(first,' ',last) AS name FROM users WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->bind_result($res_id, $res_email, $res_name);
        } else {
            throw new Exception('Invalid Token');
        }

        // Execution of User Creation
        if(isset($stmt)) {
            $stmt.execute();
            $stmt.fetch();
            $stmt.free_result();

            // Add to 'logins'
            $stmt2 = $mysqli->prepare("INSERT INTO logins(user,success) VALUES(?,?)");
            $stmt2->bind_param("ii",$res_id,$success);
            $stmt2->execute();

            // Populate User
            $User->user_id= = $res_id;
            $User->user_name = $res_name;
            $User->user_email = $res_email;
            $res = $mysqli->query("SELECT role FROM roles WHERE user={$res_id};")->fetch_all(MYSQLI_NUM);
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
                return $this->duties();
            case 'punts':
                return $this->punts();
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
            "name" => $this->User->user_name,
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
    private function duties() {
        // Taken from dashboard.php
        $sunday = strtotime("last Sunday 12:00am")-100;
        $limit = isset($_GET["all"])?"":" LIMIT 2";
        $duties_query = "(SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$this->User->user_id} AND checker <= 0 AND start > FROM_UNIXTIME({$sunday}) ORDER BY start ASC,dutyname ASC) UNION (SELECT id,start AS time,checker,(SELECT title FROM housedutieslkp WHERE id=r.duty) AS dutyname FROM houseduties r WHERE user={$this->User->user_id} AND start > FROM_UNIXTIME({$sunday}) AND checker > 0 ORDER BY start DESC,dutyname ASC{$limit});";
        $duties = $mysqli->query($duties_query)->fetch_all(MYSQLI_ASSOC);

        return $duties;
    }

    /**
     * Method for /account/punts [GET]
     *
     * @return mixed|array Array of punts
     */
    private function punts() {
        $punts_query = "SELECT timestamp,comment,makeup_given_by,IF(p.given_by>0,(SELECT CONCAT(first,' ',last) FROM users WHERE id=p.given_by),'Delts Manager') AS givenname FROM punts p WHERE user={$this->User->user_id} ORDER BY timestamp DESC";
        $punts = $mysqli->query($punts_query)->fetch_all(MYSQLI_ASSOC);

        return $punts;
    }


    /**
     * Method for /account/checkoff [POST]
     * @return int
     * @throws Exception
     */
    private function post_checkoff() {
        $json_data = json_decode($this->file);

        if(array_key_exists("duty_id", $json_data)) {
            $duty_id = $mysqli->real_escape_string($json_data["duty_id"]);
        } else {
            throw new Exception("Duty ID not found");
        }
        $res = $mysqli->prepare("UPDATE houseduties SET checker=-1,checktime=CURRENT_TIMESTAMP WHERE id=? AND checker=0 AND user=?");
        $res->bind_param("ii",$duty_id, $this->User->user_id);
        $res->execute();

        // TODO: implement status codes
        if($res->affected_rows > 0) {
            return 1;
        } else {
            return 0;
        }
    }
}