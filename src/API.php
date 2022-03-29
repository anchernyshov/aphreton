<?php

namespace Aphreton;

/**
 * Main API class
 * 
 * Usage example:
 * $api = new \Aphreton\API();
 * $api->run();
 */
class API {

    /**
     * @var \Aphreton\APIRequest
     */
    private $request;
    /**
     * @var \Aphreton\Models\User
     */
    private $user;
    /**
     * @var \Aphreton\APIResponse
     */
    private $response;
    /**
     * @var \JsonSchema\Validator
     */
    private $json_validator;
    /**
     * @var array
     */
    private $config;
    /**
     * @var string
     */
    private const CONFIG_PATH = 'config/config.php';
    /**
     * @var int
     */
    public const ERROR_TYPE_AUTH = 1;
    /**
     * @var int
     */
    public const ERROR_TYPE_NOT_FOUND = 2;

    public function __construct() {
        error_reporting( E_ALL );
        ini_set('display_errors', 0);
        set_error_handler([$this, 'errorHandler'], E_ALL);
        register_shutdown_function([$this, 'errorShutdown']);

        $this->json_validator = new \JsonSchema\Validator();
        $this->request = new \Aphreton\APIRequest();
        $this->response = new \Aphreton\APIResponse();
    }

    /**
     * API entry point
     * 
     * This function should be called right after instantiating \Aphreton\API class
     * 
     * @return void
     */
    public function run() {
        if (file_exists(self::CONFIG_PATH)) {
            $this->config = include(self::CONFIG_PATH);
        } else {
            //config error - config does not exist
            $this->triggerError('API error');
        }
        $this->initializeAPIFromConfig();
        $this->validateInput();
        
        $input_data = $this->request->getData();
        $route = $input_data->route;
        $endpoint = $input_data->endpoint;
        $params = property_exists($input_data, 'params') ? $input_data->params : null;
        
        $this->response->setRoute($route);
        $this->response->setEndpoint($endpoint);
        
        $token = $this->getBearerToken();
        if (!$token) {
            if ( (strcasecmp($route, 'auth') != 0) || (strcasecmp($endpoint, 'login') != 0) ) {
                $this->triggerError('No authentication token provided', self::ERROR_TYPE_AUTH);
            }
        } else {
            $this->authenticate($token);
        }

        $this->process($route, $endpoint, $params);
        $this->out();
    }

    /**
     * Generates and prints JSON response then terminates the program
     * 
     * @return void
     */
    public function out() {
        header('Content-Type: application/json');
        echo $this->response->toJSON();
        exit(1);
    }

    /**
     * Sets error display ini variable for debugging purposes
     * 
     * @param bool $flag
     * 
     * @return void
     */
    public function setErrorReportPolicy(bool $flag) {
        ini_set('display_errors', (int)$flag);
    }

    /**
     * Returns value of configuration file variable with given name
     * 
     * Parameter $base defines offset in the config tree. By default $base point to the root of the config
     * Triggers an error if request is not valid
     * 
     * @param string $name
     * @param array $base (optional)
     * 
     * @return array|string
     */
    public function getConfigVar(string $name, array $base = null) {
        if (!$base) {
            $base = $this->config;
        }
        if (!array_key_exists($name, $base)) {
            //config error - requested key does not exist
            $this->triggerError('API error');
        }
        return $base[$name];
    }

    /**
     * Getter for $this->user
     * 
     * @return \Aphreton\Models\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Returns filtered client IP address
     * 
     * @return string
     */
    public function getClientIPAddress() {
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * Performs validation of given array against given JSON schema
     * 
     * @param array $data Data to validate
     * @param array $schema JSON schema
     * 
     * @return string Empty string if no errors found, combined string with errors otherwise
     */
    public function validateJSONSchema($data, $schema) {		
        $this->json_validator->validate($data, $schema, \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL);
        $errstr = '';
        if (!$this->json_validator->isValid()) {
            $number_of_errors = count($this->json_validator->getErrors());
            $i = 0;
            foreach ($this->json_validator->getErrors() as $error) {
                $errstr .= ($error['property'] ? "[{$error['property']}] " : '') . $error['message'];
                $errstr .= ((++$i < $number_of_errors) ? '; ' : '');
            }
        }
        return $errstr;
    }

    /**
     * Decodes given JWT with the key from configuration file
     * 
     * @param string $token
     * 
     * @return object
     */
    public function decodeToken($token) {
        try {
            return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->config['jwt_key'], 'HS256'), 'HS256');
        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->triggerError('Authentication token expired', self::ERROR_TYPE_AUTH);
        } catch (\Exception $e) {
            $this->triggerError('Authentication token error', self::ERROR_TYPE_AUTH);
        }
    }

    /**
     * Encodes given object into JWT with the key from configuration file
     * 
     * @param object|array $payload
     * 
     * @return string
     */
    public function encodeTokenPayload($payload) {
        return \Firebase\JWT\JWT::encode($payload, $this->config['jwt_key'], 'HS256');
    }

    /**
     * Initializes API class fields from configuration file
     * 
     * @return void
     */
    private function initializeAPIFromConfig() {
        foreach ($this->config['databases'] as $name => $database) {
            $dsn = $this->getConfigVar('dsn', $database);
            $user = $this->getConfigVar('user', $database);
            $password = $this->getConfigVar('password', $database);
            \Aphreton\DatabasePool::getInstance()->addDatabase($name, $dsn, $user, $password);
        }
    }

    /**
     * Validates client HTTP request
     * 
     * Performs checks:
     * - Request method should be POST
     * - Content type should be application/json
     * - Request body should not be empty
     * - Request body should be valid JSON
     * - Request body should pass JSON schema validation
     * Triggers an error if request is not valid
     * 
     * @return void
     */
    private function validateInput() {
        if(!isset($_SERVER['REQUEST_METHOD']) || strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
            $this->triggerError('API requests are required to use POST method');
        }
        if (!isset($_SERVER['CONTENT_TYPE']) || strcasecmp($_SERVER['CONTENT_TYPE'], 'application/json') != 0) {
            $this->triggerError('API requests are required to use Content-Type: application/json header');
        }
        $input = file_get_contents('php://input');
        filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (!$input) {
            $this->triggerError('Request is empty');
        }
        $input = json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->triggerError('Request is not a valid JSON');
        }
        
        $this->request->setData($input);
        $errors = $this->validateJSONSchema($this->request->getData(), $this->request->getJSONSchema());
        if (!empty($errors)) {
            $this->triggerError("Request validation error. {$errors}");
        }
    }

    /**
     * Checks if given JWT is valid
     * 
     * Triggers an error if JWT is not valid
     * 
     * @param string $token
     * 
     * @return void
     */
    private function authenticate($token) {
        $token_payload = (array) $this->decodeToken($token);
        $client_ip = $this->getClientIPAddress();
        if (strcasecmp($token_payload['ip'], $client_ip) != 0) {
            //Client IP address mismatch
            $this->triggerError('Authentication token error', self::ERROR_TYPE_AUTH);
        }
        $this->user = \Aphreton\Models\User::get(['login' => $token_payload['login']]);
    }

    /**
     * Main routing function
     * 
     * Attempts to find given $route class and calls $endpoint method with given $params
     * 
     * @param string $route
     * @param string $endpoint
     * @param array $params
     * 
     * @return void
     */
    private function process($route, $endpoint, $params) {
        $class = null;
        $class_str = 'Aphreton\\Routes\\' . $route;
        if (class_exists($class_str)) {
            if (is_subclass_of($class_str, 'Aphreton\\APIRoute')) {
                $class = new $class_str($this);
            } else {
                $this->triggerError("API route {$route} is not valid");
            }
        } else {
            $this->triggerError("API route {$route} not exists", self::ERROR_TYPE_NOT_FOUND);
        }
        
        if ($class && method_exists($class, $endpoint)) {
            //TODO: Access control
            $schema = $class->getJSONSchemaForEndpoint($endpoint);
            if ($schema) {
                $errors = $this->validateJSONSchema($params, $schema);
                if (!empty($errors)) {
                    $this->triggerError("API route {$route} endpoint {$endpoint} data validation error. {$errors}");
                }
            }
            try {
                $this->response->setData($class->{$endpoint}($params));
            } catch (\Aphreton\AuthException $e) {
                $this->triggerError("API route {$route} endpoint {$endpoint} error: {$e->getMessage()}", self::ERROR_TYPE_AUTH);
            } catch (\Exception $e) {
                $this->triggerError("API route {$route} endpoint {$endpoint} error: {$e->getMessage()}");
            }
        } else {
            $this->triggerError("API route {$route} endpoint {$endpoint} not exists", self::ERROR_TYPE_NOT_FOUND);
        }
    }

    /**
     * Stops the program from executing with given error and type
     * 
     * This function sets HTTP response code according to error type and passes error handling to registered shutdown function
     * $type == self::ERROR_TYPE_AUTH -> HTTP error code 401
     * $type == ERROR_TYPE_NOT_FOUND -> HTTP error code 404
     * $type not specified -> HTTP error code 500
     * 
     * @param string $message
     * @param int{self::ERROR_TYPE_AUTH|self::ERROR_TYPE_NOT_FOUND} $type (optional)
     * 
     * @return string
     */
    private function triggerError(string $message, int $type = 0) {
        $code = 500;
        switch ($type) {
            case self::ERROR_TYPE_AUTH:
                $code = 401; break;
            case self::ERROR_TYPE_NOT_FOUND:
                $code = 404; break;
        }
        http_response_code($code);
        trigger_error($message, E_USER_ERROR);
    }

    /**
     * Shutdown function for handling fatal/user errors
     * 
     * @return void
     */
    public function errorShutdown() {
        $error = error_get_last();
        if ($error) {
            $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Error handler function for generating proper response when fatal/user error occurs
     * 
     * @param string $type
     * @param string $error
     * @param string $file
     * @param string $line
     * 
     * @return void
     */
    public function errorHandler($type, $error, $file, $line) {
        if (0 === error_reporting()) {
            return false;
        }
        switch ($type) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_PARSE:
                $this->response->setError('API Error');
                break;
            case E_USER_ERROR:
                $this->response->setError($error);
                break;
        }
        $this->out();
    }

    /**
     * Returns HTTP authorization header if set
     * 
     * @return string|null
     */
    private function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Returns bearer token from HTTP authorization header
     * 
     * @return string|null
     */
    private function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}

class AuthException extends \Exception {}
