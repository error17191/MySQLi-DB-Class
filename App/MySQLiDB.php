<?php

namespace App;

class MySQLiDB
{
    public $count = 0;

    private $mysqli;
    private static $_instance;
    private $connections = [];
    private $whereClause = '';

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

    public function addConnection($name, array $params)
    {
        $this->connections[$name] = new MySQLiDB($params);
    }

    public function connection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
    }

    public function get($tableName, $numRows = null, $columns = '*')
    {
        if ($columns != '*') {
            $columns = implode(',', $columns);
        }
        $sql = "SELECT {$columns} from {$tableName}";
        if ($this->whereClause) {
            $sql .= " {$this->whereClause} ";
        }
        if ($numRows) {
            $sql .= " LIMIT {$numRows}";
        }
        $results = $this->fetch($sql);
        $this->count = count($results);
        return $results;
    }

    public function getOne($tableName, $columns = '*')
    {
        foreach ($this->get($tableName, 1, $columns) as $result) {
            return $result;
        }
    }

    public function insert($tableName, $insertData)
    {
        $this->mysqli->query($this->buildInsertQuery($tableName, $insertData, 'INSERT'));
    }

    public function insertMulti($tableName, array $multiInsertData, array $dataKeys = null)
    {
        $this->mysqli->query($this->buildInsertQuery($tableName, $multiInsertData, 'INSERT', true));
    }

    public function replace($tableName, $insertData)
    {
        $this->mysqli->query($this->buildInsertQuery($tableName, $insertData, 'REPLACE'));
    }

    public function update($tableName, $tableData, $numRows = null)
    {
        $sql = "UPDATE {$tableName} SET ";
        foreach ($tableData as $key => $value) {
            if (is_string($value)) {
                $sql .= "{$key} = '{$value}',";
            } else if (is_array($value) && isset($value['inc'])) {
                $sql .= "{$key} = {$key} + {$value['inc']},";
            } else if (is_array($value) && isset($value['dec'])) {
                $sql .= "{$key} = {$key} - {$value['dec']},";
            } else {
                $sql .= "{$key} = {$value},";
            }
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $this->mysqli->query($sql);
    }

    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->whereClause = "WHERE `{$whereProp}` {$operator} " . (is_string($whereValue) ? "'{$whereValue}'" : "{$whereValue}");
        return $this;
    }

    public function inc($num = 1)
    {
        return ['inc' => $num];
    }

    public function dec($num = 1)
    {
        return ['dec' => $num];
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

    private function fetch($sql)
    {
        return $this->mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
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