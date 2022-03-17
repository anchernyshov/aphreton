<?php

namespace Aphreton;

class API {
	
	private $request;
	private $response;
	
	public function __construct() {
		error_reporting( E_ALL );
		ini_set("display_errors", 0);
		set_error_handler([$this, "errorHandler"], E_ALL);
		register_shutdown_function([$this, "errorShutdown"]);
		
		$this->request = new \Aphreton\APIRequest(file_get_contents('php://input'));
		$this->response = new \Aphreton\APIResponse();
	}
	
	public function run() {
		$this->request->validate();
		$this->out();
	}
	
	public function out() {
		header('Content-Type: application/json');
		if ($this->response->hasError()) {
			http_response_code(500);
		}
		echo $this->response->toJSON();
		exit(1);
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
				$this->response->setError("API Error");
				break;
			case E_USER_ERROR:
				$this->response->setError($error);
				break;
		}
		$this->out();
	}
	
	public function setErrorReportPolicy(bool $flag) {
		ini_set("display_errors", (int)$flag);
	}
}