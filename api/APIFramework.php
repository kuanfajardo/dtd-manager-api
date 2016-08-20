<?php
// TODO: implement success codes
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 7/22/16
 * Time: 11:23 AM
 */
abstract class APIFramework
{
    /**
     * @var string the HTTP method this request was made in : GET, POST, PUT, DELETE
     */
    protected $method = '';

    /**
     * @var string the Model requested in the URI: eg. /files
     */
    protected $endpoint = '';

    /**
     * @var string An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';

    /**
     * @var array Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();

    /**
     * @var null|string Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     *
     * @param $request string URI request
     * @throws Exception errors
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
                $this->file = file_get_contents("php://input");
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
     * Main method of api; called from api.php with request
     *
     * @return string Response based on request passed
     */
    public function processAPI() {
        if(method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}());
        }

        return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    /**
     * Clean up? (idk i copied this code)
     *
     * @param $data mixed Any data
     * @return array|string Cleaned up data
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
     * Create JSON-encoded response with HTTP Header from HTTP status code and raw data
     *
     * @param $data mixed Data to return upward
     * @param $status int HTTP status code pertaining to data
     * @return string JSON-encoded data
     */
    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    /**
     * Method to get string representation of HTTP stats code from number
     *
     * @param $code int HTTP status code
     * @return mixed string representation of $code
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