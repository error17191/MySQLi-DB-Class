<?php

namespace App;

class MySQLiDB
{
    private $mysqli;
    private static $_instance;

    public function __construct($host = null, $username = null, $password = null, $dbname = null, $port = null, $charset = 'utf8', $socket = null)
    {
        if ($host instanceof \mysqli) {
            $this->mysqli = $host;
            return;
        }

        if (is_array($host)) {
            $params = $host; // array assignment copy the elements
            $host = isset($params['host']) ? $params['host'] : null;
            $username = isset($params['username']) ? $params['username'] : null;
            $password = isset($params['password']) ? $params['password'] : null;
            $dbname = isset($params['dbname']) ? $params['dbname'] : null;
        }

        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        self::$_instance = $this;

    }

    public function getOne($tableName)
    {
        return $this->mysqli->query("SELECT * from `{$tableName}` limit 1")->fetch_assoc();
    }

    public static function getInstance()
    {
        return self::$_instance;
    }
}