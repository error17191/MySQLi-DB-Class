<?php

require_once 'vendor/autoload.php';

//$db = new MysqliDb(['host' => 'localhost']);
//$db->connect();

//$db->getOne('users');

$db = new MysqliDb([
    'host' => 'localhost',
    'username' => 'root',
    'password' => '123456',
    'db' => 'permissions'
]);

