<?php

namespace Aphreton;

/**
 * Base class for all route classes
 * 
 * To create custom route you need to extend APIRoute class and call setJSONSchemaForEndpoint
 * for each endpoint to enable JSON schema parameters validation:
 * 
 * class Test extends \Aphreton\APIRoute {
 *     public function __construct($parent) {
 *         parent::__construct($parent);
 *         $this->setJSONSchemaForEndpoint('default', [...]);
 *     }
 *     public function default($params) {...}
 * }
 */
class APIRoute {

    /**
     * Reference to main API class
     * @var \Aphreton\API
     */
    protected $parent;
    /**
     * Array containing JSON schemas for each endpoint name
     * @var array
     */
    protected $schema = [];

    protected function __construct($parent) {
        $this->parent = $parent;
    }

    /**
     * Sets JSON schema for endpoint parameters validation
     * 
     * @param string $name Endpoint name
     * @param array $schema JSON schema array
     * 
     * @return void
     */
    protected function setJSONSchemaForEndpoint(string $name, array $schema) {
        $this->schema[$name] = $schema;
    }

    /**
     * Gets JSON schema for endpoint with given name
     * 
     * @param string $name Endpoint name
     * 
     * @return array|null
     */
    public function getJSONSchemaForEndpoint(string $name) {
        if (array_key_exists($name, $this->schema)) {
            return $this->schema[$name];
        }
        return null;
    }
}
