<?php

namespace Aphreton;

class API {
	
	private $request;
	private $response;
	
	public function __construct() {
		$this->request = new \Aphreton\APIRequest(file_get_contents('php://input'));
		$this->response = new \Aphreton\APIResponse();
	}
	
	public function run() {
		$this->request->validate();
		return $this->response->toJSON();
	}
	
}