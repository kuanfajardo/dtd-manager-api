<?php
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/24/16
 * Time: 4:27 PM
 */

namespace Models;


class User
{
    /**
     * @var string Username
     */
    public $user_name;

    /**
     * @var int User ID
     */
    public $user_id;

    /**
     * @var array Array of user privileges
     */
    public $user_privileges;

    /**
     * @var string User Email
     */
    public $user_email;


    public function __construct() {

    }

    /**
     * Method to verify token
     *
     * @param $token User token to verify
     * @return bool token is valid
     */
    public function verify_token($token) {
        
    }

    /**
     * Method to return email from given token
     *
     * @param $token User token to verify
     * @return string Email from given token
     */
    public function email_from_token($token) {

    }

}