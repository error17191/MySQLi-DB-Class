<?php

require_once 'vendor/autoload.php';

use App\MySQLiDB;

//$db = new MySQLiDB('localhost', 'root', '123456', 'travninja');
$db = new MySQLiDB([
    'host' => 'localhost',
    'username' => 'root',
    'password' => '123456',
    'dbname' => 'permissions'
]);

print_r($db->getOne('roles'));
var_dump($db->count);

//$mysqli = new mysqli('localhost', 'root', '123456', 'travninja');
//$db = new MySQLiDB($mysqli);
//$db = new MySQLiDB();

//print_r($db->getOne('users'));