<?php
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/24/16
 * Time: 4:26 PM
 */

namespace Models;


final class APIKeyFactory
{
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return APIKeyFactory The *Singleton* instance.
     */
    public static function Instance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new APIKeyFactory();
        }

        return $inst;
    }

    /**
     * APIKeyFactory constructor.
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    private function __construct() {
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
    }

    /**
     * Method to check whether given api key is valid
     *
     * @param $key string API key to be verified
     * @param $origin string origin of request (user)
     * @return bool Key is valid
     */
    public static function verify_key($key, $origin) {
        return true;
    }

    /**
     * Method to generate api key
     *
     * @param $origin string Origin of request
     * @return string generated api key
     */
    public function generate_key($origin) {
        return '';
    }
}