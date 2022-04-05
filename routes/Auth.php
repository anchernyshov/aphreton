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
        $user = \Aphreton\Models\User::get(['login' => $params->login, 'password' => $params->password]);
        if ($user) {
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
                'Attempt to authenticate with invalid credentials',
                \Aphreton\Models\LogEntry::LOG_LEVEL_WARNING,
                'Incorrect username or password',
                \Aphreton\APIException::ERROR_TYPE_AUTH
            );
        }
        return $result;
    }
}
