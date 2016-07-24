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
                $User->user_privileges[] = $r[0];
            }
        }

        // Assign local to global
        $this->User = $User;
    }}