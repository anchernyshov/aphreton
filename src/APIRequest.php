<?php

namespace Aphreton;

/**
 * Represents the request to the API
 */
class APIRequest {

    /**
     * Parsed JSON request
     * @var object
     */
    private $data;
    /**
     * JSON schema to validate against $data
     * @var array
     */
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

    /**
     * Setter for $this->data
     * 
     * @param object $data
     * 
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Getter for $this->data
     * 
     * @return object
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Getter for $this->schema
     * 
     * @return array
     */
    public function getJSONSchema() {
        return $this->schema;
    }
}
