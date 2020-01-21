<?php

namespace App;

class MySQLiDB
{
    private $mysqli;
    private static $_instance;
    private $connections = [];

    public function __construct($host = null, $username = null, $password = null, $dbname = null, $port = null, $charset = 'utf8', $socket = null)
    {
        if ($host instanceof \mysqli) {
            $this->mysqli = $host;
            return;
        }

        if (is_array($host)) {
            $params = $host; // array assignment copy the elements
            foreach ($params as $key => $value) {
                $$key = $value;
            }
        }

        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        self::$_instance = $this;
        $this->connections['default'] = $this;

    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function addConnection($name, $params)
    {
        if ($name == 'default') {
            return;
        }
        $this->connections[$name] = new MySQLiDB($params);
    }

    public function connection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
    }

    public function getOne($tableName)
    {
        return $this->mysqli->query("SELECT * from `{$tableName}` limit 1")->fetch_assoc();
    }

    public function insert($table, $data)
    {
        $sql = "INSERT INTO {$table} (";
        $columns = '';
        foreach ($data as $key => $value) {
            $columns .= "{$key},";
        }
        $columns = substr($columns, 0, strlen($columns) - 1);
        $sql .= $columns . ') ';
        $values = 'VALUES(';
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $values .= "'{$value}',";
            } else {
                $values .= "{$value},";
            }
        }
        $values = substr($values, 0, strlen($values) - 1);
        $sql .= $values . ')';
    }

}