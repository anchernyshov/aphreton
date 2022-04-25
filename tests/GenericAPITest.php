<?php

namespace Aphreton\Tests;

require_once("../vendor/autoload.php");

class GenericAPITest extends HTTPAPITestBase {
	
    public function testGet() {
        $response = $this->client->request('GET', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost'
            ],
            'http_errors' => false
        ]);
        $this->errorResponseCheck($response, 500, 'API requests are required to use POST method');
    }

    public function testPostWithoutContentType() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost'
            ],
            'http_errors' => false
        ]);
        $this->errorResponseCheck($response, 500, 'API requests are required to use Content-Type: application/json header');
    }

    public function testPostWithoutBody() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false
        ]);
        $this->errorResponseCheck($response, 500, 'Request is empty');
    }

    public function testPostMalformedJSON() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => 'test'
        ]);
        $this->errorResponseCheck($response, 500, 'Request is not a valid JSON');
    }

    public function testPostEmptyJSON() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{}'
        ]);
        $this->errorResponseCheck($response, 500, 'Request validation error. [route] The property route is required; [endpoint] The property endpoint is required');
    }

    public function testPostWithoutToken() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json'
            ],
            'http_errors' => false,
            'body' => '{"route": "gggggg", "endpoint": "gggggg"}'
        ]);
        $this->errorResponseCheck($response, 401, 'No authentication token provided');
    }

    public function testPostWithMalformedToken() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer GGGGGGGG'
            ],
            'http_errors' => false,
            'body' => '{"route": "gggggg", "endpoint": "gggggg"}'
        ]);
        $this->errorResponseCheck($response, 401, 'Authentication token error');
    }

    public function testPostWithExpiredToken() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpbiI6InRlc3QxIiwiaXAiOiI6OjEiLCJleHAiOjE2NTA2NTkzODAuMjY3NTU2fQ.GoJC22OotXVM3xXqGsV3By5LTvVjWrxt2PEI9XKk_Kk'
            ],
            'http_errors' => false,
            'body' => '{"route": "gggggg", "endpoint": "gggggg"}'
        ]);
        $this->errorResponseCheck($response, 401, 'Authentication token expired');
    }

    public function testRequestUnknownRoute() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpbiI6InRlc3QiLCJpcCI6Ijo6MSIsImV4cCI6NDgwNDI1OTgwNC42OTQxMzh9.lWFYSdANlbGEQJo-aw7PzNKI71Hac1tPw09O4Ijfcg0'
            ],
            'http_errors' => false,
            'body' => '{"route": "gggggg", "endpoint": "gggggg"}'
        ]);
        $this->errorResponseCheck($response, 404, 'API route gggggg is not exists');
    }

    public function testRequestUnknownEndpoint() {
        $response = $this->client->request('POST', 'http://localhost/', [
            'headers' => [
                'Host' => 'localhost',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpbiI6InRlc3QiLCJpcCI6Ijo6MSIsImV4cCI6NDgwNDI1OTgwNC42OTQxMzh9.lWFYSdANlbGEQJo-aw7PzNKI71Hac1tPw09O4Ijfcg0'
            ],
            'http_errors' => false,
            'body' => '{"route": "auth", "endpoint": "gggggg"}'
        ]);
        $this->errorResponseCheck($response, 404, 'API route auth endpoint gggggg is not exists');
    }
}
