<?php

namespace Aphreton;

class APIRequest implements \JsonSerializable {
	
	private $post_data;
	private $json_validator;
	private $schema = [
		"type" => "object",
		"properties" => [
			"method" => [
				"type" => "string"
			],
			"action" => [
				"type" => "string"
			],
			"data" => [
				"type" => "object"
			]
		],
		"required" => ["method", "action"]
	];
	
	public function __construct($input) {
		$this->json_validator = new \JsonSchema\Validator();
		$this->post_data = json_decode($input);
	}
	
	public function validate() {		
		if (!$this->post_data) {
			throw new \Exception('Request is empty');
		}
		$this->json_validator->validate($this->post_data, $this->schema, \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL);
		if (!$this->json_validator->isValid()) {
			$errstr = "";
			foreach ($this->json_validator->getErrors() as $error) {
				$errstr .= $error['property'] . ": " . $error['message'] . "; ";
			}
			throw new \Exception($errstr);
		}
	}
	
	public function jsonSerialize() {
        return [
            'params' => $this->post_data
        ];
    }
	
	public function toJSON() {
		return json_encode($this);
	}
}