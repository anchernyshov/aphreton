<?php

namespace Aphreton;

class API {
	
	private $response;
	
	public function __construct() {
		$this->response = new \Aphreton\APIResponse();
	}
	
	public function run() {
		return $this->response->toJSON();
	}
	
}