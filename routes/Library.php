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
}
