<?php

namespace Aphreton;

class APIRequest implements \JsonSerializable {
	
	private $post_data;
	private $json_validator;
	private $schema = [
		'type' => 'object',
		'properties' => [
			'route' => [
				'type' => 'string'
			],
			'endpoint' => [
				'type' => 'string'
			],
			'params' => [
				'type' => 'object'
			]
		],
		'required' => ['route', 'endpoint']
	];
	
	public function __construct($input) {
		$this->json_validator = new \JsonSchema\Validator();
		$this->post_data = json_decode($input);
	}
	
	public function validate() {		
		if (!$this->post_data) {
			trigger_error('Request is empty', E_USER_ERROR);
		}
		$this->json_validator->validate($this->post_data, $this->schema, \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL);
		if (!$this->json_validator->isValid()) {
			$errstr = '';
			$number_of_errors = count($this->json_validator->getErrors());
			$i = 0;
			foreach ($this->json_validator->getErrors() as $error) {
				$errstr .= ($error['property'] ? "[{$error['property']}] " : '') . $error['message'];
				$errstr .= ((++$i < $number_of_errors) ? '; ' : '');
			}
			trigger_error("Request validation error. {$errstr}", E_USER_ERROR);
		}
	}
	
	public function getParsedData() {
		return $this->post_data;
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