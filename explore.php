<?php

require_once 'vendor/autoload.php';

//$db = new MysqliDb(['host' => 'localhost']);
//$db->connect();

//$db->getOne('users');

new MysqliDb([
    'host' => 'localhost',
    'username' => 'root',
    'password' => '123456',
    'db' => 'travninja'
]);

new MysqliDb([
    'host' => 'localhost',
    'username' => 'root',
    'password' => '123456',
    'db' => 'permissions'
]);

print_r(MysqliDb::getInstance()->getOne('users'));