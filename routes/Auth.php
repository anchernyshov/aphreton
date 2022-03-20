<?php

namespace Aphreton\Routes;

class Auth extends \Aphreton\APIRoute {
	
	private $JWT_valid_duration = 1 * 1 * 60; //1 minute for testing purposes

	public function __construct($parent) {
        parent::__construct($parent);
		$this->setJSONSchemaForEndpoint(
			'login', [
				'type' => 'object',
				'properties' => [
					'login' => [
						'type' => 'string'
					],
					'password' => [
						'type' => 'string'
					]
				],
				'required' => ['login', 'password']
			]
		);
    }
	
	public function login($params) {
		$result = [];
		$client_ip = $this->parent->getClientIPAddress();
		//test login/pass combination
		if (strcasecmp($params->login, 'test') == 0 && strcmp($params->password, 'qwerty') == 0) {
			$payload = [
				'login' => $params->login,
				'ip' => $client_ip,
				'exp' => microtime(true) + $this->JWT_valid_duration
			];
			$result['token'] = $this->parent->encodeTokenPayload($payload);
		} else {
			$this->parent->toggleAuthError();
			throw new \Exception('Incorrect password');
		}
		return $result;
	}
}