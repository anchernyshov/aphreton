<?php

namespace Aphreton;

class APIRequest implements \JsonSerializable {
	
	private $post_data;
	
	public function __construct($input) {
		$this->post_data = json_decode($input);
	}
	
	public function validate() {		
		if (!$this->post_data) {
			throw new \Exception('Request is empty');
		}
	}
	
	public function jsonSerialize() {
        return [
            'params' => $this->post_data
        ];
    }
	
	public function toJSON() {
		return json_encode($this);
	}
}