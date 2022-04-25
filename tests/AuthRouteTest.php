<?php

namespace Aphreton\Tests;

require_once("../vendor/autoload.php");

class AuthRouteTest extends HTTPAPITestBase {

    private $response_schemas = [
        'login' => [
            'type' => 'array',
            'properties' => [
                'token' => [
                    'type' => 'string'
                ]
            ],
            'required' => ['token']
        ]
    ];
	
    public function testLoginJSONSchemaValidation1() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "login"}'
        ]);
        $this->errorResponseCheck($response, 500, 'Endpoint data validation error. NULL value found, but an object is required');
    }

    public function testLoginJSONSchemaValidation2() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "login", "params": {}}'
        ]);
        $this->errorResponseCheck($response, 500, 'Endpoint data validation error. [login] The property login is required; [password] The property password is required');
    }

    public function testLoginJSONSchemaValidation3() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "login", "params": {"login": 1, "password": 1}}'
        ]);
        $this->errorResponseCheck($response, 500, 'Endpoint data validation error. [login] Integer value found, but a string is required; [password] Integer value found, but a string is required');
    }

    public function testLoginWithIncorrectUserData() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "login", "params": {"login": "notexists", "password": "123123"}}'
        ]);
        $this->errorResponseCheck($response, 401, 'Incorrect username or password');
    }

    public function testLoginWithCorrectUserData() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "login", "params": {"login": "test", "password": "qwerty"}}'
        ]);
        $this->successResponseCheck($response, $this->response_schemas['login']);
        
        $body = json_decode($response->getBody(), true);
        $decoded_token = \Firebase\JWT\JWT::decode($body['data']['token'], new \Firebase\JWT\Key($this->getConfigVar('jwt_key'), 'HS256'), 'HS256');
        $this->assertEquals('test', $decoded_token->login);
    }
}
