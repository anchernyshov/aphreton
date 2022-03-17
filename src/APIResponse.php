<?php

namespace Aphreton;

class APIResponse implements \JsonSerializable {
	
	public int $status = 1;
	private string $error = "";
	private $execution_time = null;
	
	public function __construct() {
		$this->execution_time = -microtime(true);
	}
	
	public function setError(string $error) {
		$this->status = 0;
		$this->error = $error;
	}
	
	public function hasError() {
		return !(bool)$this->status;
	}
	
	public function jsonSerialize() {
        return [
            'status' => $this->status,
			'error' => $this->error,
			'execution_time' => $this->execution_time
        ];
    }

	public function toJSON() {
		$this->execution_time += microtime(true);
		return json_encode($this);
	}
}