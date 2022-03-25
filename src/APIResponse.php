<?php

namespace Aphreton;

/**
 * Represents the response from the API
 */
class APIResponse implements \JsonSerializable {

    /**
     * Response status
     * 
     * $this->status = 1 if no errors detected, 0 otherwise
     * 
     * @var int
     */
    public int $status = 1;
    /**
     * Requested API route
     * 
     * @var string
     */
    private string $route = '';
    /**
     * Requested API endpoint
     * 
     * @var string
     */
    private string $endpoint = '';
    /**
     * Error message
     * 
     * @var string
     */
    private string $error = '';
    /**
     * Data from requested API endpoint
     * 
     * @var array
     */
    private array $data = array();
    /**
     * Time of program execution
     * 
     * @var float
     */
    private $execution_time = null;

    public function __construct() {
        $this->execution_time = -microtime(true);
    }

    /**
     * Setter for $this->route
     * 
     * @param string $route
     * 
     * @return void
     */
    public function setRoute(string $route) {
        $this->route = $route;
    }

    /**
     * Setter for $this->endpoint
     * 
     * @param string $endpoint
     * 
     * @return void
     */
    public function setEndpoint(string $endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Setter for $this->data
     * 
     * @param array $data
     * 
     * @return void
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     * Sets response error status with given error string
     * 
     * @param string $error
     * 
     * @return void
     */
    public function setError(string $error) {
        $this->status = 0;
        $this->error = $error;
    }

    /**
     * Checks if response has error status
     * 
     * @return bool
     */
    public function hasError() {
        return !(bool)$this->status;
    }

    /**
     * Serializes this instance to object
     * 
     * @return array
     */
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

    /**
     * Returns JSON encoded instance string
     * 
     * This method is designed to be used at the end of the program right before final echo
     * Total time of program execution is calculated before JSON return
     * 
     * @return string
     */
    public function toJSON() {
        $this->execution_time += microtime(true);
        return json_encode($this);
    }
}
