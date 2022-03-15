<?php

error_reporting( E_ALL );

require_once("./aphreton/API.php");

$api = new \Aphreton\API();
$result = $api->run();

header('Content-Type: application/json');
echo($result);