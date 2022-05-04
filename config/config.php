<?php

return [
    'timezone' => 'Europe/Moscow',
    'jwt_key' => 'example-key',
    'jwt_valid_duration' => 1 * 1 * 60, //1 minute for testing purposes
    'password_pepper' => 'a2pAdls3m62KW3d',
    'log_enable' => false,
    'log_database' => 'logs',
    'initialize_database' => true, //Full reset and reinitialization, all data will be lost!
    'databases' => [
        'main' => [
            'dsn' => 'sqlite:main.sqlite3',
            'user' => '',
            'password' => ''
        ],
        'logs' => [
            'dsn' => 'mongodb://localhost:27017/?authSource=aphreton&readPreference=primary&ssl=false',
            'user' => 'logger',
            'password' => 'test'
        ]
    ]
];
