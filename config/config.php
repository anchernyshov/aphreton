<?php 
return [
    'timezone' => 'Europe/Moscow',
    'jwt_key' => 'example-key',
    'jwt_valid_duration' => 60,
    'password_pepper' => 'a2pAdls3m62KW3d',
    'log_enable' => false,
    'log_database' => 'logs',
    'initialize_database' => false,
    'databases' => [
        'main' => [
            'dsn' => 'sqlite:main.sqlite3',
            'user' => '',
            'password' => '',
        ],
        'logs' => [
            'dsn' => 'mongodb://localhost:27017/?authSource=aphreton&readPreference=primary&ssl=false',
            'user' => 'logger',
            'password' => 'test',
        ],
    ],
];