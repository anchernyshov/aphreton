<?php

namespace Aphreton;

class APIRoute {

    protected $parent;
	protected $schema = [];
	
	protected function __construct($parent) {
        $this->parent = $parent;
    }
	
	protected function setJSONSchemaForEndpoint($name, $schema) {
		$this->schema[$name] = $schema;
	}
	
	public function getJSONSchemaForEndpoint($name) {
		if (array_key_exists($name, $this->schema)) {
			return $this->schema[$name];
		}
		return null;
	}
}