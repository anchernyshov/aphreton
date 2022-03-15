<?php

namespace Aphreton;

require_once("./aphreton/APIResponse.php");

class API {
	
	private $response;
	
	public function __construct() {
		$this->response = new \Aphreton\APIResponse();
	}
	
	public function run() {
		return $this->response->toJSON();
	}
	
}