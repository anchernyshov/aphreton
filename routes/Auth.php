<?php

namespace Aphreton\Routes;

class Auth extends \Aphreton\APIRoute {

    public function __construct($parent) {
        parent::__construct($parent);
        $this->setJSONSchemaForEndpoint(
            'login', [
                'type' => 'object',
                'properties' => [
                    'login' => [
                        'type' => 'string'
                    ],
                    'password' => [
                        'type' => 'string'
                    ]
                ],
                'required' => ['login', 'password']
            ]
        );
        $this->setJSONSchemaForEndpoint(
            'refresh', [
                'type' => 'object',
                'properties' => [
                    'login' => [
                        'type' => 'string'
                    ],
                    'refresh_token' => [
                        'type' => 'string'
                    ]
                ],
                'required' => ['login', 'refresh_token']
            ]
        );
        $this->setRequiredUserLevelForEndpoint('refresh', 0);
        $this->setJSONSchemaForEndpoint(
            'logout', [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ]
        );
        $this->setRequiredUserLevelForEndpoint('logout', 1);
        $this->setJSONSchemaForEndpoint(
            'register', [
                'type' => 'object',
                'properties' => [
                    'login' => [
                        'type' => 'string'
                    ],
                    'password' => [
                        'type' => 'string'
                    ]
                ],
                'required' => ['login', 'password']
            ]
        );
    }

    public function login($params) {
        $result = [];
        $client_ip = $this->parent->getUser()->getIPAddress();
        $user = \Aphreton\Models\User::getOne(['login' => $params->login]);
        if ($user) {
            $pepper = $this->parent->getConfigVar('password_pepper');
            if (password_verify(hash_hmac("sha512", $params->password, $pepper), $user->password)) {
                $user->last_logined = date('Y-m-d H:i:s');
                $refresh_token = $this->parent->getUser()->encodeTokenPayload([
                    'login' => $params->login,
                    'exp' => microtime(true) + $this->parent->getConfigVar('refresh_token_valid_duration')
                ]);
                // Saving refresh token to database
                $user->refresh_token = $refresh_token;
                $user->save();
                $result['access_token'] = $this->parent->getUser()->encodeTokenPayload([
                    'login' => $params->login,
                    'ip' => $client_ip,
                    'exp' => microtime(true) + $this->parent->getConfigVar('jwt_valid_duration')
                ]);
                $result['refresh_token'] = $refresh_token;
            } else {
                throw new \Aphreton\APIException(
                    'Attempt to authenticate with invalid password for user ' . $params->login,
                    \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                    'Incorrect username or password',
                    \Aphreton\APIException::ERROR_TYPE_AUTH
                );
            }
        } else {
            throw new \Aphreton\APIException(
                'Attempt to authenticate with invalid credentials',
                \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                'Incorrect username or password',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
        return $result;
    }

    public function refresh($params) {
        $result = [];
        $client_ip = $this->parent->getUser()->getIPAddress();
        $user = \Aphreton\Models\User::getOne(['login' => $params->login]);
        if (!$user) {
            throw new \Aphreton\APIException(
                'User for JWT refresh is not found',
                \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                'Incorrect username',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
        if (strcmp($user->refresh_token, $params->refresh_token) != 0) {
            // Token mismatch
            throw new \Aphreton\APIException(
                'Attempt to refresh JWT with incorrect token',
                \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                'Incorrect refresh token',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        } else {
            $refresh_token = $this->parent->getUser()->encodeTokenPayload([
                'login' => $user->login,
                'exp' => microtime(true) + $this->parent->getConfigVar('refresh_token_valid_duration')
            ]);
            // Saving refresh token to database
            $user->refresh_token = $refresh_token;
            $user->save();
            $result['access_token'] = $this->parent->getUser()->encodeTokenPayload([
                'login' => $user->login,
                'ip' => $client_ip,
                'exp' => microtime(true) + $this->parent->getConfigVar('jwt_valid_duration')
            ]);
            $result['refresh_token'] = $refresh_token;
        }
        return $result;
    }

    public function logout($params) {
        $result = [];
        $user = $this->parent->getUser()->getModel();
        $user->refresh_token = "";
        $user->save();
        return $result;
    }

    public function register($params) {
        $result = [];
        $user = \Aphreton\Models\User::getOne(['login' => $params->login]);
        if (!$user) {
            $user = new \Aphreton\Models\User();
            $user->login = $params->login;
            $pepper = $this->parent->getConfigVar('password_pepper');
            $peppered_password = hash_hmac("sha512", $params->password, $pepper);
            $user->password = password_hash($peppered_password, PASSWORD_BCRYPT, ['cost' => 11]);
            $user->save();
        } else {
            throw new \Aphreton\APIException(
                'Attempt to register an account with existing username',
                \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                'Username already exists',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
        return $result;
    }
}
