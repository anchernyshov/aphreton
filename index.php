<?php

error_reporting( E_ALL );
ini_set("display_errors", 1);

require_once("./vendor/autoload.php");

$api = new \Aphreton\API();
$result = $api->run();

header('Content-Type: application/json');
echo($result);