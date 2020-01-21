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

$db->insertMulti('roles', [
    [
        'name' => 'Admin',
    ],
    [
        'name' => 'Supervisor',
    ],
]);
//$mysqli = new mysqli('localhost', 'root', '123456', 'travninja');
//$db = new MySQLiDB($mysqli);
//$db = new MySQLiDB();

//print_r($db->getOne('users'));