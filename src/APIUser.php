<?php

namespace Aphreton;

/**
 * Represents the API user
 */
class APIUser {
    /**
     * @var ?string
     */
    private $ip_address = null;
    /**
     * @var \Aphreton\Models\User
     */
    private $model = null;
    /**
     * @var ?string
     */
    private $jwt_key = null;

    public function __construct($jwt_key) {
        $this->ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        $this->jwt_key = $jwt_key;
    }

    /**
     * Getter for $this->ip_address
     * 
     * @return string
     */
    public function getIPAddress() {
        return $this->ip_address;
    }

    /**
     * Returns true if $this->model is not null
     * 
     * @return bool
     */
    public function isAuthenticated() {
        return ($this->model != null);
    }

    /**
     * Getter for $this->model
     * 
     * @return \Aphreton\Models\User
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Checks if given JWT is valid and populates user model with the values from the database
     * 
     * Triggers an error if JWT is not valid
     * 
     * @param string $token
     * 
     * @return void
     */
    public function loadFromJWT($token) {
        $token_payload = (array) $this->decodeToken($token);
        if (strcasecmp($token_payload['ip'], $this->ip_address) != 0) {
            throw new \Aphreton\APIException(
                'Client IP address mismatch. IP address from token: ' . $token_payload['ip'],
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR,
                'Authentication token error',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
        $this->model = \Aphreton\Models\User::getOne(['login' => $token_payload['login']]);
    }

    /**
     * Decodes given JWT with the provided key
     * 
     * @param string $token
     * 
     * @return object
     */
    private function decodeToken($token) {
        try {
            return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->jwt_key, 'HS256'), 'HS256');
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new \Aphreton\APIException(
                'Attempt to authenticate with expired token',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR,
                'Authentication token expired',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        } catch (\Exception $e) {
            throw new \Aphreton\APIException(
                'Authentication token error: ' . $e->getMessage(),
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR,
                'Authentication token error',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
    }

    /**
     * Encodes given object into JWT with the provided key
     * 
     * @param object|array $payload
     * 
     * @return string
     */
    public function encodeTokenPayload($payload) {
        return \Firebase\JWT\JWT::encode($payload, $this->jwt_key, 'HS256');
    }
}
