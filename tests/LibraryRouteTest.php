<?php

namespace Aphreton\Tests;

require_once("../vendor/autoload.php");

class LibraryRouteTest extends HTTPAPITestBase {

    private $response_schemas = [
        'add_author' => [
            'type' => 'object',
            'properties' => [
                '_id' => [
                    'type' => 'string',
                    'enum' => ['1']
                ],
                'name' => [
                    'type' => 'string',
                    'enum' => ['Test Author']
                ]
            ],
            'required' => ['_id', 'name']
        ],
        'get_author' => [
            'type' => 'array',
            'items' => [
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'enum' => ['Test Author']
                    ]
                ],
                'required' => ['name']
            ]
        ],
        'add_book' => [
            'type' => 'object',
            'properties' => [
                '_id' => [
                    'type' => 'string',
                    'enum' => ['1']
                ],
                'name' => [
                    'type' => 'string',
                    'enum' => ['Test Book']
                ],
                'author_id' => [
                    'type' => 'integer',
                    'enum' => [1]
                ],
                'price' => [
                    'type' => 'integer',
                    'enum' => [100]
                ]
            ],
            'required' => ['_id', 'name', 'author_id', 'price']
        ],
        'get_book' => [
            'type' => 'array',
            'items' => [
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'enum' => ['Test Book']
                    ],
                    'author_id' => [
                        'type' => 'string',
                        'enum' => ['1']
                    ],
                    'price' => [
                        'type' => 'string',
                        'enum' => ['100']
                    ]
                ],
                'required' => ['name', 'author_id', 'price']
            ]
        ]
    ];
	
    public function testAddAuthorEndpoint() {
        $response = $this->APIRequest('library', 'add_author', ['name' => 'Test Author'], true);
        $this->successResponseCheck($response, $this->response_schemas['add_author']);
    }

    public function testGetAuthorEndpoint() {
        $response = $this->APIRequest('library', 'get_author', ['name' => 'Test Author'], true);
        $this->successResponseCheck($response, $this->response_schemas['get_author']);
    }

    public function testGetAuthorEndpointFail() {
        $response = $this->APIRequest('library', 'get_author', ['name' => 'ABC'], true);
        $this->errorResponseCheck($response, 500, 'Author with name ABC does not exist');
    }

    public function testAddBookEndpoint() {
        $response = $this->APIRequest('library', 'add_book', ['name' => 'Test Book', 'price' => 100, 'author_id' => 1], true);
        $this->successResponseCheck($response, $this->response_schemas['add_book']);
    }

    public function testGetBookEndpoint1() {
        $response = $this->APIRequest('library', 'get_book', ['book_name' => 'Test Book'], true);
        $this->successResponseCheck($response, $this->response_schemas['get_book']);
    }

    public function testGetBookEndpoint2() {
        $response = $this->APIRequest('library', 'get_book', ['author_name' => 'Test Author'], true);
        $this->successResponseCheck($response, $this->response_schemas['get_book']);
    }

    public function testDeleteBookEndpoint() {
        $response = $this->APIRequest('library', 'delete_book', ['id' => 1], true);
        $this->successResponseCheck($response, null);
    }

    public function testDeleteBookEndpointFail() {
        $response = $this->APIRequest('library', 'delete_book', ['id' => 1], true);
        $this->errorResponseCheck($response, 500, 'Book with id 1 does not exist');
    }

    public function testDeleteAuthorEndpoint() {
        $response = $this->APIRequest('library', 'delete_author', ['id' => 1], true);
        $this->successResponseCheck($response, null);
    }

    public function testDeleteAuthorEndpointFail() {
        $response = $this->APIRequest('library', 'delete_author', ['id' => 1], true);
        $this->errorResponseCheck($response, 500, 'Author with id 1 does not exist');
    }

    public function testGetAuthorEndpointAfterDeletion() {
        $response = $this->APIRequest('library', 'get_author', ['name' => 'Test Author'], true);
        $this->errorResponseCheck($response, 500, 'Author with name Test Author does not exist');
    }
}
