<?php

/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/22/16
 * Time: 11:23 AM
 */
abstract class APIFramework
{
    /**
     * Property: method
     * the HTTP method this request was made in : GET, POST, PUT, DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * the Model requested in the URI: eg. /files
     */
    protected $endpoint = '';

    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request) {
        // CORS: allows all access AND set content to JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        // Create array of args from request
        $this->args = explode('/', rtrim($request, '/'));

        // First arg is endpoint
        $this->endpoint = array_shift($this->args);

        // Check if second arg is verb
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }

        // Set method
        $this->method = $_SERVER['REQUEST_METHOD'];

        // Check for hidden PUT and DELETE methods
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            switch ($_SERVER['HTTP_X_HTTP_METHOD']) {
                case 'DELETE':
                    $this->method = 'DELETE';
                    break;
                case 'PUT':
                    $this->method = 'PUT';
                    break;
                default:
                    throw new Exception("Unexpected Header");
                    break;
            }
        }

        // Clean up method
        // if PUT, set file
        switch ($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    /**
     * @return string Response based on request passed
     */
    public function processAPI() {
        if(method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }

        return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    /**
     * @param $data Any data
     * @return array|string Cleaned up (idk i copied this code)
     */
    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    /**
     * @param $data Data to return upward
     * @param int $status HTTP status code pertaining to data
     * @return string JSON-encoded data
     */
    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    /**
     * @param $code HHTP status code
     * @return mixed String representation of $code
     */
    private function _requestStatus($code) {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        );
        return ($status[$code] ? $status[$code] : $status[500]);
    }
}