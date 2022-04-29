<?php

namespace Aphreton\Routes;

class Library extends \Aphreton\APIRoute {

    public function __construct($parent) {
        parent::__construct($parent);
        $this->setJSONSchemaForEndpoint(
            'get_book', [
                'type' => 'object',
                'properties' => [
                    'book_name' => [
                        'type' => ['string', 'array']
                    ],
                    'author_name' => [
                        'type' => ['string', 'array']
                    ]
                ],
                'anyOf' => [
                    ["required" => ["book_name"]],
				    ["required" => ["author_name"]]
                ]
            ]
        );
        $this->setRequiredUserLevelForEndpoint('get_book', 1);
        $this->setJSONSchemaForEndpoint(
            'add_author', [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string'
                    ]
                ],
                'required' => ['name']
            ]
        );
        $this->setRequiredUserLevelForEndpoint('add_author', 1);
        $this->setJSONSchemaForEndpoint(
            'add_book', [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string'
                    ],
                    'price' => [
                        'type' => 'integer'
                    ],
                    'author_name' => [
                        'type' => 'string'
                    ],
                    'author_id' => [
                        'type' => 'integer'
                    ]
                ],
                'oneOf' => [
                    ["required" => ['name', 'price', "author_name"]],
                    ["required" => ['name', 'price', "author_id"]]
                ]
            ]
        );
        $this->setRequiredUserLevelForEndpoint('add_book', 1);
    }

    public function get_book($params) {
        $filter = [];
        if (property_exists($params, 'book_name')) {
            $filter['name'] = $params->book_name;
        }
        if (property_exists($params, 'author_name')) {
            $author = \Aphreton\Models\Author::get(['name' => $params->author_name]);
            if (!$author) {
                throw new \Aphreton\APIException(
                    'Author with name ' . $params->author_name . ' does not exist',
                    \Aphreton\Models\LogEntry::LOG_LEVEL_INFO,
                    'Author with name ' . $params->author_name . ' does not exist'
                );
            }
            $id_list = [];
            foreach ($author as $key => $value) {
                $id_list[] = $value->getId();
            }
            $filter['author_id'] = $id_list;
        }
        $books = \Aphreton\Models\Book::get($filter);
        return $books;
    }

    public function add_author($params) {
        $author = new \Aphreton\Models\Author();
        $author->name = $params->name;
        $author->save();
        return $author->toArray();
    }

    public function add_book($params) {
        $book = new \Aphreton\Models\Book();
        $book->name = $params->name;
        $book->price = $params->price;
        if (property_exists($params, 'author_id')) {
            $author = \Aphreton\Models\Author::getOne(['_id' => $params->author_id]);
            if (!$author) {
                throw new \Aphreton\APIException(
                    'Author with id ' . $params->author_id . ' does not exist',
                    \Aphreton\Models\LogEntry::LOG_LEVEL_INFO,
                    'Author with id ' . $params->author_id . ' does not exist'
                );
            }
            $book->author_id = $params->author_id;
        } else if (property_exists($params, 'author_name')) {
            $author = \Aphreton\Models\Author::getOne(['name' => $params->author_name]);
            if (!$author) {
                throw new \Aphreton\APIException(
                    'Author with name ' . $params->author_name . ' does not exist',
                    \Aphreton\Models\LogEntry::LOG_LEVEL_INFO,
                    'Author with name ' . $params->author_name . ' does not exist'
                );
            }
            $book->author_id = $author->getId();
        }
        $book->save();
        return $book->toArray();
    }
}
