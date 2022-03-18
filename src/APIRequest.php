<?php

namespace Aphreton;

class APIRequest {
	
	private $data;
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
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getJSONSchema() {
		return $this->schema;
	}
}