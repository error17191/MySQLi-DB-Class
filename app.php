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

print_r($db->where('id', 5, '>')->get('roles'));
