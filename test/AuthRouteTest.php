<?php

require_once("../vendor/autoload.php");

class AuthRouteTest extends \PHPUnit\Framework\TestCase {

    private $client;
    private $json_validator;
    private $config;
    private const CONFIG_PATH = '../config/config.php';
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

    public function setUp(): void {
        $this->client = new \GuzzleHttp\Client();
        $this->json_validator = new \JsonSchema\Validator();
        $this->config = include(self::CONFIG_PATH);
    }

    private function errorResponseCheck($response, $error_code, $error = null) {
        $this->assertEquals($error_code, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Malformed response JSON');
        }
        $this->assertEquals(0, $body['status']);
        if ($error) {
            $this->assertEquals($error, $body['error']);
        }
    }

    private function responseCheck($response, $schema = null) {
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $response->getBody()->seek(0);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Malformed response JSON');
        }
        $this->assertEquals(1, $body['status']);
        if ($schema) {
            $this->json_validator->validate($body['data'], $schema, \JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL);
            $errstr = '';
            if (!$this->json_validator->isValid()) {
                $number_of_errors = count($this->json_validator->getErrors());
                $i = 0;
                foreach ($this->json_validator->getErrors() as $error) {
                    $errstr .= ($error['property'] ? ('[' . $error['property'] . '] ') : '') . $error['message'];
                    $errstr .= ((++$i < $number_of_errors) ? '; ' : '');
                }
                throw new \Exception('Response JSON schema is not valid: ' . $errstr);
            }
        }
    }
	
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
        $this->responseCheck($response, $this->response_schemas['login']);
        
        $body = json_decode($response->getBody(), true);
        $decoded_token = \Firebase\JWT\JWT::decode($body['data']['token'], new \Firebase\JWT\Key($this->config['jwt_key'], 'HS256'), 'HS256');
        $this->assertEquals('test', $decoded_token->login);
    }
}
