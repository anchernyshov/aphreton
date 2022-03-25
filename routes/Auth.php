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
    }

    public function login($params) {
        $result = [];
        $client_ip = $this->parent->getClientIPAddress();

        /* TODO: Main database preparation on first launch
            CREATE TABLE "USERS" (
                "id"	INTEGER NOT NULL UNIQUE,
                "login"	TEXT NOT NULL UNIQUE,
                "password"	TEXT NOT NULL,
                PRIMARY KEY("id" AUTOINCREMENT)
            );
        */
        
        $user = \Aphreton\DatabasePool::getInstance()->getDatabase('test')->query(
            "SELECT * FROM USERS WHERE login = :login AND password = :password", 
            ['login' => $params->login, 'password' => $params->password]
        );

        if ($user) {
            $payload = [
                'login' => $params->login,
                'ip' => $client_ip,
                'exp' => microtime(true) + $this->parent->getConfigVar('jwt_valid_duration')
            ];
            $result['token'] = $this->parent->encodeTokenPayload($payload);
        } else {
            throw new \Aphreton\AuthException('Incorrect password');
        }
        return $result;
    }
}
