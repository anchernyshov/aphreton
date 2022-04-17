<?php

namespace Aphreton\Routes;

class Library extends \Aphreton\APIRoute {

    public function __construct($parent) {
        parent::__construct($parent);
        $this->setJSONSchemaForEndpoint(
            'get', [
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
        $this->setRequiredUserLevelForEndpoint('get', 1);
    }

    public function get($params) {
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
            if (is_array($author)) {
                $id_list = [];
                foreach ($author as $key => $value) {
                    $id_list[] = $value->getId();
                }
                $filter['author_id'] = $id_list;
            } else {
                $filter['author_id'] = $author->getId();
            }
        }
        $books = \Aphreton\Models\Book::get($filter);
        if ($books !== null && !is_array($books)) {
            $books = [$books];
        }
        return $books;
    }
}
