<?php

namespace Aphreton;

class API {
	
	private $request;
	private $response;
	private $json_validator;
	
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
		$this->validateInput();
		
		$input_data = $this->request->getData();
		$route = $input_data->route;
		$endpoint = $input_data->endpoint;
		$params = property_exists($input_data, 'params') ? $input_data->params : null;
		
		$this->response->setRoute($route);
		$this->response->setEndpoint($endpoint);
		
		$this->process($route, $endpoint, $params);
		$this->out();
	}
	
	public function validateInput() {
		if(!isset($_SERVER['REQUEST_METHOD']) || strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
			trigger_error('API requests are required to use POST method', E_USER_ERROR);
		}
		if (!isset($_SERVER['CONTENT_TYPE']) || strcasecmp($_SERVER['CONTENT_TYPE'], 'application/json')) {
			trigger_error('API requests are required to use Content-Type: application/json header', E_USER_ERROR);
		}
		$input = file_get_contents('php://input');
		filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if (!$input) {
			trigger_error('Request is empty', E_USER_ERROR);
		}
		$input = json_decode($input);
		if (json_last_error() !== JSON_ERROR_NONE) {
			trigger_error('Request is not a valid JSON', E_USER_ERROR);
		}
		
		$this->request->setData($input);
		$errors = $this->validateJSONSchema($this->request->getData(), $this->request->getJSONSchema());
		if (!empty($errors)) {
			trigger_error("Request validation error. {$errors}", E_USER_ERROR);
		}
	}
	
	public function process($route, $endpoint, $params) {
		$class = null;
		$class_str = 'Aphreton\\Routes\\' . $route;
		if (class_exists($class_str)) {
			if (is_subclass_of($class_str, 'Aphreton\\APIRoute')) {
				$class = new $class_str($this);
			} else {
				trigger_error("API route {$route} is not valid", E_USER_ERROR);
			}
		} else {
			trigger_error("API route {$route} not exists", E_USER_ERROR);
		}
		
		if ($class && method_exists($class, $endpoint)) {
			//TODO: Access control
			$schema = $class->getJSONSchemaForEndpoint($endpoint);
			if ($schema) {
				$errors = $this->validateJSONSchema($params, $schema);
				if (!empty($errors)) {
					trigger_error("API route {$route} endpoint {$endpoint} data validation error. {$errors}", E_USER_ERROR);
				}
			}
			try {
				$this->response->setData($class->{$endpoint}($params));
			} catch (\Exception $e) {
				trigger_error("API route {$route} endpoint {$endpoint} error: {$e->getMessage()}", E_USER_ERROR);
			}
		} else {
			trigger_error("API route {$route} endpoint {$endpoint} not exists", E_USER_ERROR);
		}
	}
	
	public function out() {
		header('Content-Type: application/json');
		if ($this->response->hasError()) {
			http_response_code(500);
		}
		echo $this->response->toJSON();
		exit(1);
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
	
	public function setErrorReportPolicy(bool $flag) {
		ini_set('display_errors', (int)$flag);
	}
}