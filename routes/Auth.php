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
        $client_ip = $this->parent->getClientIPAddress();
        $user = \Aphreton\Models\User::getOne(['login' => $params->login]);
        if ($user) {
            $pepper = $this->parent->getConfigVar('password_pepper');
            if (password_verify(hash_hmac("sha512", $params->password, $pepper), $user->password)) {
                $user->last_logined = date('Y-m-d H:i:s');
                $user->save();
                $payload = [
                    'login' => $params->login,
                    'ip' => $client_ip,
                    'exp' => microtime(true) + $this->parent->getConfigVar('jwt_valid_duration')
                ];
                $result['token'] = $this->parent->encodeTokenPayload($payload);
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
