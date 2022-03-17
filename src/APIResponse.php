<?php

namespace Aphreton;

class APIResponse implements \JsonSerializable {
	
	public int $status = 1;
	private string $route = '';
	private string $endpoint = '';
	private string $error = '';
	private array $data = array();
	private $execution_time = null;
	
	public function __construct() {
		$this->execution_time = -microtime(true);
	}
	
	public function setRoute(string $route) {
		$this->route = $route;
	}
	
	public function setEndpoint(string $endpoint) {
		$this->endpoint = $endpoint;
	}
	
	public function setData(array $data) {
		$this->data = $data;
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
			'route' => $this->route,
			'endpoint' => $this->endpoint,
			'data' => $this->data,
			'error' => $this->error,
			'execution_time' => $this->execution_time
        ];
    }

	public function toJSON() {
		$this->execution_time += microtime(true);
		return json_encode($this);
	}
}