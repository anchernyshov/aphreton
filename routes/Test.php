<?php

namespace Aphreton\Routes;

class Test extends \Aphreton\APIRoute {

	public function __construct($parent) {
        parent::__construct($parent);
		$this->setJSONSchemaForEndpoint(
			'default', [
				"type" => "object",
				"properties" => [
					"message" => [
						"type" => "string"
					]
				],
				"required" => ["message"]
			]
		);
    }
	
	public function default($params) {
		return ['Hello from Test route default endpoint!'];
	}
	
	public function testException($params) {
		throw new \Exception('Exception from Test route default endpoint!');
	}
	
	public function testError($params) {
		callNonExistingMethod();
	}
}