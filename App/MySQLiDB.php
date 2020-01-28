<?php

namespace App;

class MySQLiDB
{
    public $count = 0;
    public $pageLimit = 2;
    public static $prefix;

    private $mysqli;
    private static $_instance;
    private $connections = [];
    private $whereClause = '';
    private $whereBindings;
    private $limit;
    private $queryBindings = [];

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

        if (isset($prefix)) {
            $this->setPrefix($prefix);
        }

    }

    public function setPrefix($prefix = '')
    {
        self::$prefix = $prefix;
        return $this;
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
        if ($numRows) {
            $this->limit = $numRows;
        } else {
            $this->limit = null;
        }
        $results = $this->fetch("SELECT {$columns} from {$this->tableName($tableName)}");
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
        $this->query($this->buildInsertQuery($tableName, $insertData, 'INSERT'));
    }

    public function insertMulti($tableName, array $multiInsertData, array $dataKeys = null)
    {
        $this->query($this->buildInsertQuery($tableName, $multiInsertData, 'INSERT', true));
    }

    public function replace($tableName, $insertData)
    {
        $this->mysqli->query($this->buildInsertQuery($tableName, $insertData, 'REPLACE'));
    }

    public function update($tableName, $tableData, $numRows = null)
    {
        $sql = "UPDATE {$this->tableName($tableName)} SET ";
        $this->bindings = [];
        $this->limit = $numRows ?: null;
        foreach ($tableData as $key => $value) {
            $sql .= "{$key} = ?,";
            $this->bindings[] = $value;
//            if (is_string($value)) {
//                $sql .= "{$key} = '{$value}',";
//            } else if (is_array($value) && isset($value['inc'])) {
//                $sql .= "{$key} = {$key} + {$value['inc']},";
//            } else if (is_array($value) && isset($value['dec'])) {
//                $sql .= "{$key} = {$key} - {$value['dec']},";
//            } else {
//                $sql .= "{$key} = {$value},";
//            }
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $this->query($sql);
    }

    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->whereClause = "WHERE `{$whereProp}` {$operator} ?";
        $this->whereBindings = [$whereProp => $whereValue];
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

    public function has($tableName)
    {
        $sql = "SELECT count(*) from `{$this->tableName($tableName)}`" . ($this->whereClause ? ' ' . $this->whereClause : '');
        return $this->mysqli->query($sql)->fetch_assoc()['count(*)'] >= 1;
    }

    public function paginate($table, $page, $fields = null)
    {
        $offset = ($page - 1) * $this->pageLimit;
        $columns = '*';
        if (!$fields) {
            $columns = '*';
        } elseif (is_array($fields)) {
            $columns = implode(',', $fields);
        } elseif (is_string($fields)) {
            $columns = $fields;
        }
        $sql = "SELECT {$columns} FROM {$this->tableName($table)} LIMIT {$this->pageLimit} OFFSET {$offset}";
        $results = $this->fetch($sql);
        $this->count = count($results);
        return $results;
    }

    public function getValue($tableName, $column, $limit = 1)
    {
        $results = $this->get($tableName, $limit, [$column]);
        if (count($results) == 0) {
            return null;
        }
        if (count($results) == 1) {
            return $results[0][$column];
        }
        $values = [];
        foreach ($results as $result) {
            $values[] = $result[$column];
        }

        return $values;
    }

    private function query($sql)
    {
        if ($this->whereClause) {
            $sql .= " " . $this->whereClause;
            $this->bindings = array_merge($this->bindings, $this->whereBindings);
        }
        if ($this->limit) {
            $sql .= " LIMIT ?";
            $this->bindings[] = $this->limit;
        }
        $stmt = $this->mysqli->prepare($sql);
        $typesString = '';
        foreach ($this->bindings as $binding) {
            if (is_int($binding)) {
                $typesString .= 'i';
            } else if (is_double($binding)) {
                $typesString .= 'd';
            } else {
                $typesString .= 's';
            }
        }
        if (count($this->bindings) > 0) {
            $stmt->bind_param($typesString, ...array_values($this->bindings));
        }
        $stmt->execute();
        return $stmt->get_result();
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
        return $this->query($sql)->fetch_all(MYSQLI_ASSOC);
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

    private function tableName($tableName)
    {
        return self::$prefix . $tableName;
    }
}