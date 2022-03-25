<?php

namespace Aphreton;

class API {
	
	private $request;
	private $response;
	private $json_validator;
	private $config;
	private const CONFIG_PATH = 'config/config.php';
	public const ERROR_TYPE_AUTH = 1;
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

	private function initializeAPIFromConfig() {
		foreach ($this->config['databases'] as $name => $database) {
			$dsn = $this->getConfigVar('dsn', $database);
			$user = $this->getConfigVar('user', $database);
			$password = $this->getConfigVar('password', $database);
			\Aphreton\DatabasePool::getInstance()->addDatabase($name, $dsn, $user, $password);
		}
	}
	
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
	
	private function authenticate($token) {
		$token_payload = (array) $this->decodeToken($token);
		$client_ip = $this->getClientIPAddress();
		if (strcasecmp($token_payload['ip'], $client_ip) != 0) {
			//Client IP address mismatch
			$this->triggerError('Authentication token error', self::ERROR_TYPE_AUTH);
		}
	}
	
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
	
	public function getClientIPAddress() {
		return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
	}
	
	public function decodeToken($token) {
		try {
			return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->config['jwt_key'], 'HS256'), 'HS256');
		} catch (\Firebase\JWT\ExpiredException $e) {
			$this->triggerError('Authentication token expired', self::ERROR_TYPE_AUTH);
		} catch (\Exception $e) {
			$this->triggerError('Authentication token error', self::ERROR_TYPE_AUTH);
		}
	}
	
	public function encodeTokenPayload($payload) {
		return \Firebase\JWT\JWT::encode($payload, $this->config['jwt_key'], 'HS256');
	}
	
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
	
	public function out() {
		header('Content-Type: application/json');
		echo $this->response->toJSON();
		exit(1);
	}
	
	public function triggerError(string $message, int $type = 0) {
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
	
	public function errorShutdown() {
		$error = error_get_last();
		if ($error) {
			$this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}

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

	private function getBearerToken() {
		$headers = $this->getAuthorizationHeader();
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}
	
	public function setErrorReportPolicy(bool $flag) {
		ini_set('display_errors', (int)$flag);
	}
}

class AuthException extends \Exception {}