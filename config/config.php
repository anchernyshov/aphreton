<?php

return [
	'jwt_key' => 'example-key',
	'jwt_valid_duration' => 1 * 1 * 60, //1 minute for testing purposes
	'databases' => [
		'test' => [
			'dsn' => 'sqlite:test.sqlite3',
			'user' => '',
			'password' => ''
		]
	]
];