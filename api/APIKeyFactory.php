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
        if ($key == '3Ha63GR28fbkknu29HUb1Qk3RO2NR9ga') {
            return true;
        }

        return false;
    }

    /**
     * Method to generate api key
     *
     * @param $origin string Origin of request
     * @return string generated api key
     */
    public function generate_key($origin) {
        return '3Ha63GR28fbkknu29HUb1Qk3RO2NR9ga';
        //return $this->random_str(25);
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int $length      How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     * @return string
     */
    function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }
}