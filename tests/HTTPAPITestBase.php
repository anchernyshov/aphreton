<?php

namespace Aphreton\Tests;

require_once("../vendor/autoload.php");

class HTTPAPITestBase extends \PHPUnit\Framework\TestCase {

    protected $client;
    protected $json_validator;
    protected $config;
    protected const CONFIG_PATH = '../config/config.php';

    protected function setUp(): void {
        $this->client = new \GuzzleHttp\Client();
        $this->json_validator = new \JsonSchema\Validator();
        if (file_exists(self::CONFIG_PATH)) {
            $this->config = include(self::CONFIG_PATH);
        } else {
            throw new \Exception('Config file does not exist');
        }
    }

    protected function APIRequest(string $route, string $endpoint, array $data = null, bool $use_token = false) {
        $params_block = '';
        if ($data !== null) {
            $params_block = ', "params": ' . json_encode($data,  JSON_FORCE_OBJECT);
        }
        $headers = [
            'Host' => 'localhost',
            'Content-Type' => 'application/json'
        ];
        if ($use_token === true) {
            $headers['Authorization'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpbiI6InRlc3QiLCJpcCI6Ijo6MSIsImV4cCI6NDgwNDI1OTgwNC42OTQxMzh9.lWFYSdANlbGEQJo-aw7PzNKI71Hac1tPw09O4Ijfcg0';
        }
        return $this->client->request('POST', 'http://localhost/', [
            'headers' => $headers,
            'http_errors' => false,
            'body' => '{"route": "' . $route . '", "endpoint": "' . $endpoint . '"' . $params_block . '}'
        ]);
    }

    protected function getConfigVar(string $name, array $base = null) {
        if (!$base) {
            $base = $this->config;
        }
        if (!array_key_exists($name, $base)) {
            throw new \Exception('Configuration error: key ' . $name . ' does not exist');
        }
        return $base[$name];
    }

    protected function errorResponseCheck($response, $error_code, $error = null) {
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

    protected function successResponseCheck($response, $schema = null) {
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
}
