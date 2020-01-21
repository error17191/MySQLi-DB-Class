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
        $this->connections[$name] = new MySQLiDB($params);
    }

    public function connection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
    }

    public function get($tableName, $numRows = null, $columns = null)
    {
        if (!$columns) {
            $columnsSql = '*';
        } else {
            $columnsSql = implode(',', $columns);
        }

        return $this->mysqli->query("SELECT {$columnsSql} from {$tableName} LIMIT {$numRows}")->fetch_all();
    }

    public function getOne($tableName, $columns = null)
    {
        foreach ($this->get($tableName, 1, $columns) as $result) {
            return $result;
        }
    }

    public function insert($table, $data)
    {
        $this->mysqli->query($this->buildInsertQuery($table, $data, 'INSERT'));
    }

    public function insertMulti($table, $data)
    {
        $this->mysqli->query($this->buildInsertQuery($table, $data, 'INSERT', true));
    }

    public function replace($table, $data)
    {
        $this->mysqli->query($this->buildInsertQuery($table, $data, 'REPLACE'));
    }

    public function replaceMulti($table, $data)
    {
        $this->mysqli->query($this->buildInsertQuery($table, $data, 'REPLACE', true));
    }

    private function buildInsertQuery($table, $data, $action, $multi = false)
    {
        $sql = "{$action} INTO `{$table}`(";
        if (!$multi) {
            $sql .= implode(',', array_keys($data)) . ')';
        } else {
            $sql .= implode(',', array_keys($data[0])) . ')';
        }
        $sql .= $this->buildInsertValuesSQL($data, $multi);

        return $sql;
    }

    private function buildInsertValuesSQL($data, $multi)
    {
        $sql = ' VALUES ';
        if (!$multi) {
            $data = [$data];
        }
        foreach ($data as $record) {
            $sql .= '(';
            foreach ($record as $value) {
                if (is_string($value)) {
                    $sql .= "'{$value}',";
                } else {
                    $sql .= "{$value},";
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 1) . '),';
        }
        return substr($sql, 0, strlen($sql) - 1);
    }

}