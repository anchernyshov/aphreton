<?php

namespace Aphreton;

class APIResponse implements \JsonSerializable {
	
	private int $status = 1;
	private $execution_time = null;
	
	public function __construct() {
		$this->execution_time = -microtime(true);
	}
	
	public function jsonSerialize() {
        return [
            'status' => $this->status,
			'execution_time' => $this->execution_time
        ];
    }

	public function toJSON() {
		$this->execution_time += microtime(true);
		return json_encode($this);
	}
}