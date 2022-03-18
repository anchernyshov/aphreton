<?php

namespace Aphreton;

class APIRoute {

    protected $parent;
	
	protected function __construct($parent) {
        $this->parent = $parent;
    }
}