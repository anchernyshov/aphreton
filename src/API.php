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
     * @var \Aphreton\APIUser
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
     * @var bool
     */
    private bool $log_enable = false;
    /**
     * @var bool
     */
    private bool $log_database_available = false;
    /**
     * @var string
     */
    private const CONFIG_PATH = 'config/config.php';

    public function __construct() {
        error_reporting( E_ALL );
        ini_set('display_errors', 0);
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler'], E_ALL);
        register_shutdown_function([$this, 'errorShutdown']);

        $this->json_validator = new \JsonSchema\Validator();
        $this->request = new APIRequest();
        $this->response = new APIResponse();
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
            //log database is not initialized here - manually trigger user error
            trigger_error('API configuration error', E_USER_ERROR);
        }
        $this->initializeAPIFromConfig();
        $this->validateInput();

        $this->user = new APIUser($this->getConfigVar('jwt_key'));
        
        $input_data = $this->request->getData();
        $route = $input_data->route;
        $endpoint = $input_data->endpoint;
        $params = property_exists($input_data, 'params') ? $input_data->params : null;
        
        $this->response->setRoute($route);
        $this->response->setEndpoint($endpoint);

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
     * Returns value of configuration file variable with given name
     * 
     * Parameter $base defines offset in the config tree. By default $base point to the root of the config
     * 
     * @param string $name
     * @param array $base (optional)
     * 
     * @throws \Aphreton\APIException if request is not valid
     * 
     * @return array|string
     */
    public function getConfigVar(string $name, array $base = null) {
        if (!$base) {
            $base = $this->config;
        }
        if (!array_key_exists($name, $base)) {
            throw new APIException(
                'Configuration error: key ' . $name . ' does not exist',
                Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
        return $base[$name];
    }

    /**
     * Saves updated configuration file stored in $this->getConfigVar
     * 
     * @return null
     */
    public function saveConfig() {
        $altered_config = '<?php ' . PHP_EOL . 'return ' . $this->varexport($this->config, true) . ';';
        file_put_contents(self::CONFIG_PATH, $altered_config);
    }

    /**
     * Getter for $this->user
     * 
     * @return \Aphreton\APIUser
     */
    public function getUser() {
        return $this->user;
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
                $errstr .= ($error['property'] ? ('[' . $error['property'] . '] ') : '') . $error['message'];
                $errstr .= ((++$i < $number_of_errors) ? '; ' : '');
            }
        }
        return $errstr;
    }

    /**
     * Initializes API class fields from configuration file
     * 
     * @return void
     */
    private function initializeAPIFromConfig() {
        $this->log_enable = $this->getConfigVar('log_enable');
        //Initializing all databases
        $databases = $this->getConfigVar('databases');
        foreach ($databases as $name => $item) {
            $dsn = $this->getConfigVar('dsn', $item);
            $user = $this->getConfigVar('user', $item);
            $password = $this->getConfigVar('password', $item);
            DatabasePool::getInstance()->addDatabase($name, $dsn, $user, $password);
        }
        //Log database health check
        if ($this->log_enable) {
            $log_database_name = $this->getConfigVar('log_database');
            $this->log_database_available = DatabasePool::getInstance()->getDatabase($log_database_name)->checkConnection();
            if (!$this->log_database_available) {
                //log database is not initialized here - manually trigger user error
                trigger_error('Log database error', E_USER_ERROR);
            }
        }
        date_default_timezone_set($this->getConfigVar('timezone'));

        // Main API database reset and initialization
        if ($this->getConfigVar('initialize_database')) {
            
            $this->config['initialize_database'] = false;
            $this->saveConfig();

            $db = DatabasePool::getInstance()->getDatabase('main');

            //!!! Full database reset !!!
            $db->query("PRAGMA writable_schema = 1;", [], "");
            $db->query("DELETE FROM sqlite_master;", [], "");
            $db->query("PRAGMA writable_schema = 0;", [], "");
            $db->query("VACUUM;", [], "");
            $db->query("PRAGMA integrity_check;", [], "");

            $db->query(
                "CREATE TABLE IF NOT EXISTS \"USER\" (
                    \"_id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"login\" TEXT NOT NULL UNIQUE,
                    \"password\" TEXT NOT NULL,
                    \"level\" INTEGER NOT NULL DEFAULT 0,
                    \"last_logined\" TEXT
                );", [], ""
            );
            $user = new Models\User();
            $user->login = 'test';
            $user->level = 1;
            $pepper = $this->getConfigVar('password_pepper');
            $peppered_password = hash_hmac("sha512", 'qwerty', $pepper);
            $user->password = password_hash($peppered_password, PASSWORD_BCRYPT, ['cost' => 11]);
            $user->save();

            $db->query(
                "CREATE TABLE IF NOT EXISTS \"AUTHOR\" (
                    \"_id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"name\" TEXT NOT NULL
                );", [], ""
            );
            $db->query(
                "CREATE TABLE IF NOT EXISTS \"BOOK\" (
                    \"_id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"name\" TEXT NOT NULL,
                    \"author_id\" INTEGER NOT NULL,
                    \"price\" INTEGER NOT NULL
                );", [], ""
            );
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
            throw new APIException(
                'Wrong request method',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'API requests are required to use POST method'
            );
        }
        if (!isset($_SERVER['CONTENT_TYPE']) || strcasecmp($_SERVER['CONTENT_TYPE'], 'application/json') != 0) {
            throw new APIException(
                'Wrong request content type',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'API requests are required to use Content-Type: application/json header'
            );
        }
        $input = file_get_contents('php://input');
        //filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (!$input) {
            throw new APIException(
                'Empty request body',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'Request is empty'
            );
        }
        $input = json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new APIException(
                'Malformed request JSON',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'Request is not a valid JSON'
            );
        }
        
        $this->request->setData($input);
        $errors = $this->validateJSONSchema($this->request->getData(), $this->request->getJSONSchema());
        if (!empty($errors)) {
            throw new APIException(
                'Request validation error. ' . $errors,
                Models\LogEntry::LOG_LEVEL_WARNING,
                'Request validation error. ' . $errors
            );
        }
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
                throw new APIException(
                    'API route ' . $route . ' is not valid',
                    Models\LogEntry::LOG_LEVEL_WARNING,
                    'API route ' . $route . ' does not exist',
                    APIException::ERROR_TYPE_NOT_FOUND
                );
            }
        } else {
            throw new APIException(
                'API route ' . $route . ' does not exist',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'API route ' . $route . ' does not exist',
                APIException::ERROR_TYPE_NOT_FOUND
            );
        }
        
        if ($class && method_exists($class, $endpoint)) {
            $required_level = $class->getRequiredUserLevelForEndpoint($endpoint);
            if ($required_level > 0) {
                $token = $this->getBearerToken();
                if (!$token) {
                    //TODO: temporarily ban user without token after X attempts
                    throw new APIException(
                        'Endpoint access attempt without authentication token',
                        Models\LogEntry::LOG_LEVEL_WARNING,
                        'No authentication token provided',
                        APIException::ERROR_TYPE_AUTH
                    );
                } else {
                    $this->user->loadFromJWT($token);
                }
                if ($this->user->isAuthenticated() && $this->user->getModel()->level < $required_level) {
                    throw new APIException(
                        "User authorization error for endpoint {$route}.{$endpoint} (level required: {$required_level}, user level: {$this->user->level})",
                        Models\LogEntry::LOG_LEVEL_WARNING,
                        'Authorization error',
                        APIException::ERROR_TYPE_AUTH
                    );
                }
            }
            $schema = $class->getJSONSchemaForEndpoint($endpoint);
            if ($schema) {
                $errors = $this->validateJSONSchema($params, $schema);
                if (!empty($errors)) {
                    throw new APIException(
                        'Endpoint data validation error. ' . $errors,
                        Models\LogEntry::LOG_LEVEL_WARNING,
                        'Endpoint data validation error. ' . $errors
                    );
                }
            }
            try {
                $this->response->setData($class->{$endpoint}($params));
            } catch (APIException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new APIException(
                    'API route ' . $route . ' endpoint ' . $endpoint . ' error: ' . $e->getMessage(),
                    Models\LogEntry::LOG_LEVEL_ERROR
                );
            }
        } else {
            throw new APIException(
                'API route ' . $route .' endpoint ' . $endpoint . ' does not exist',
                Models\LogEntry::LOG_LEVEL_WARNING,
                'API route ' . $route .' endpoint ' . $endpoint . ' does not exist',
                APIException::ERROR_TYPE_NOT_FOUND
            );
        }
    }

    /**
     * Exception handler function
     * 
     * This function sets HTTP response code according to exception code and triggers 
     * E_USER_ERROR with exception message
     * 
     * $exception->getCode() == Aphreton\APIException::ERROR_TYPE_AUTH -> HTTP error code 401
     * $exception->getCode() == Aphreton\APIException::ERROR_TYPE_NOT_FOUND -> HTTP error code 404
     * $exception->getCode() not specified (0 by default) -> HTTP error code 500
     * 
     * @param string $message
     * @param int{self::ERROR_TYPE_AUTH|self::ERROR_TYPE_NOT_FOUND} $type (optional)
     * 
     * @return string
     */
    public function exceptionHandler($exception) {
        $code = 500;
        $message = APIException::DEFAULT_API_ERROR_MESSAGE;
        if ($exception instanceof APIException) {
            $this->log($exception->getLogMessage(), $exception->getLogLevel());
            switch ($exception->getCode()) {
                case APIException::ERROR_TYPE_AUTH:
                    $code = 401; break;
                case APIException::ERROR_TYPE_NOT_FOUND:
                    $code = 404; break;
            }
            $message = $exception->getMessage();
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
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $this->response->setError(APIException::DEFAULT_API_ERROR_MESSAGE);
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

    /**
     * Writes message to log database if config variable log_enable is set to true
     * 
     * @return void
     */
    private function log(string $message, int $level = null) {
        if ($this->log_enable && $this->log_database_available) {
            Models\LogEntry::create($message, $level);
        }
    }

    /**
     * var_export() with square brackets and 4 spaces indentation
     * 
     * From php.net:
     * https://www.php.net/manual/ru/function.var-export.php#122853
     * 
     * @return string
     */
    private function varexport($expression, $return=FALSE) {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        if ((bool)$return) return $export; else echo $export;
    }
}
