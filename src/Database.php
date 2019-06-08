<?php
/**
 * Created by PhpStorm.
 * User: gul
 * Date: 6/8/19
 * Time: 4:21 PM
 */

namespace Yusrub\DatabaseMysqliInterface;

use stdClass;

class Database
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $userName;

    /**
     * @var string
     */
    private $password;

    /**
     * connection to db
     */
    private $mysql;

    /**
     * instance of Database
     */
    private static $instance;

    private $result;

    /*
     * @var string
     */
    private $query;

    private $resultRows;


    /**
     * Database constructor.
     * @param string $host
     * @param $dbName
     * @param $userName
     * @param $password
     * @throws DatabaseException
     */
    public function __construct($host = 'localhost', $dbName, $userName, $password)
    {
        self::$instance = $this;
        $this->dbName = $dbName;
        $this->mysql = mysqli_connect($host, $userName, $password, $dbName);
        if (!$this->mysql) {
            throw new DatabaseException('unable to connect to database '. mysqli_connect_error());
        }
        $this->mysql->set_charset('utf8');
    }

    /**
     * @param $table
     * @param array|string $where
     * @param bool|int $limit
     * @param bool|string $orderBy
     * @param string $whereMode
     * @param string $fieldsToSelect
     * @return mixed
     * @throws DatabaseException
     */
    public function select($table, $where = [], $limit = false, $orderBy = false, $whereMode = 'AND', $fieldsToSelect = '*')
    {
        if (is_array($fieldsToSelect)) {
            $fields = '';
            foreach ($fieldsToSelect as $field) {
                $fields = '`'. $field . '`, ';
            }
            $fieldsToSelect = rtrim($fields, ', ');
        }
        $query = 'SELECT '. $fieldsToSelect. ' FROM `'. $table. '`';

        if (!empty($where)) {
            $query .= ' WHERE '. $this->processWhereStatement($where, $whereMode);
        }

        if ($orderBy) {
            $query .= ' ORDER BY '. $orderBy;
        }

        if ($limit) {
            $query .= ' LIMIT '. $limit;
        }

        return $this->executeQuery($query);
    }

    public function processWhereStatement($where, $whereMode)
    {
        $whereStatement = '';
        if (is_array($where)) {
            $num = 0;
            $where_count = count($where);
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $w = array_keys($value);
                    if (reset($w) != 0) {
                        throw new DatabaseException('Can not handle associative arrays');
                    }
                    $whereStatement .= " `" . $key . "` IN (" . implode($value, ',') . ")";
                } elseif (!is_integer($key)) {
                    $whereStatement .= ' `' . $key . "`='" . $this->escape($value) . "'";
                } else {
                    $whereStatement .= ' ' . $value;
                }
                $num++;
                if ($num != $where_count) {
                    $whereStatement .= ' ' . $whereMode;
                }
            }
        } else {
            $whereStatement .= ' '. $where;
        }
        return $whereStatement;
    }

    /**
     * @param $query
     * @return $this
     * @throws DatabaseException
     */
    public function executeQuery($query)
    {
        $this->query = $query;
        $this->resultRows = null;
        $this->result = mysqli_query($this->mysql, $query);
        if (mysqli_error($this->mysql) != '') {
            $this->result = null;
            throw new DatabaseException('Database error: ' . mysqli_error($this->mysql));
        }
        return $this;
    }

    protected function escape($str)
    {
        return mysqli_real_escape_string($this->mysql, $str);
    }

    public function getResult()
    {
        if ($this->resultRows === null) {
            $this->resultRows = [];
            while($row = mysqli_fetch_assoc($this->result)) {
                $this->resultRows[] = $row;
            }
        }
        $index = 0;
        $result = [];
        foreach ($this->resultRows as $record) {
            $result[$index] = new stdClass();
            foreach ($record as $columnName => $columnValue) {
                $result[$index]->{$columnName} = $columnValue;
            }
            $index++;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($this->resultRows === null) {
            $this->resultRows = [];
            while($row = mysqli_fetch_assoc($this->result)) {
                $this->resultRows[] = $row;
            }
        }
        $index = 0;
        $result = [];
        foreach ($this->resultRows as $record) {
            $result[$index] = [];
            foreach ($record as $columnName => $columnValue) {
                $result[$index][$columnName] = $columnValue;
            }
            $index++;
        }
        return $result;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->result) {
            return mysqli_num_rows($this->result);
        } elseif (isset($this->resultRows)) {
            return count($this->resultRows);
        } else {
            return 0;
        }
    }

    /**
     * @param $table
     * @param array|string $fields
     * @return $this
     * @throws DatabaseException
     */
    public function insert($table, $fields = [])
    {
        $this->result = null;
        $this->query = null;
        $query = 'INSERT INTO `'. $table. '`';

        if (is_array($fields)) {
            $query .= ' (';
            $num = 0;
            $values = '';
            foreach ($fields as $key => $value) {
                $query .= ' `' . $key . '`';
                $num++;
                $values .= ' \'' . $this->escape($value) . '\'';
                if ($num != count($fields)) {
                    $query .= ',';
                    $values .= ',';
                }
            }
            $query .= ' ) VALUES ( ' . $values . ' )';
        }
        echo $query."<br/><br/><br/><br/>";
        $this->query = $query;
        $this->result = mysqli_query($this->mysql, $query);
        if (mysqli_error($this->mysql) != '') {
            $this->result = null;
            throw new DatabaseException(mysqli_error($this->mysql));
        }
        return $this;
    }

    /**
     * @param $table
     * @param array $fields
     * @param array|string $where
     * @param bool $limit
     * @param bool $order
     * @return $this
     * @throws DatabaseException
     */
    public function update($table, $fields = array(), $where = array(), $limit = false, $order = false)
    {
        if (empty($where)) {
            throw new DatabaseException('Where clause is empty for update method');
        }
        $this->result = null;
        $this->query = null;
        $query = 'UPDATE `' . $table . '` SET';
        if (is_array($fields)) {
            $nr = 0;
            foreach ($fields as $k => $v) {
                if($v === null) {
                    $query .= ' `' . $k . "`=NULL";
                } else {
                    $query .= ' `' . $k . "`='" . $this->escape($v) . "'";
                }
                $nr++;
                if ($nr != count($fields)) {
                    $query .= ',';
                }
            }
        } else {
            $query .= ' ' . $fields;
        }
        if (!empty($where)) {
            $query .= ' WHERE' . $this->processWhereStatement($where, 'AND');
        }
        if ($order) {
            $query .= ' ORDER BY ' . $order;
        }
        if ($limit) {
            $query .= ' LIMIT ' . $limit;
        }
        $this->query = $query;
        $this->result = mysqli_query($this->mysql, $query);
        if (mysqli_error($this->mysql) != '') {
            $this->result = null;
            throw new DatabaseException(mysqli_error($this->mysql));
        }
        return $this;
    }

    /**
     * @param $table
     * @param array $where
     * @param string $whereMode
     * @param bool $limit
     * @param bool $order
     * @return $this
     * @throws DatabaseException
     */
    public function delete($table, $where = [], $whereMode = "AND", $limit = false, $order = false)
    {
        if (empty($where)) {
            throw new DatabaseException('Where clause is empty for update method');
        }
        // Notice: different syntax to keep backwards compatibility
        $this->result = null;
        $this->query = null;
        $query = 'DELETE FROM `' . $table . '`';
        if (!empty($where)) {
            $query .= ' WHERE' . $this->processWhereStatement($where, $whereMode);
        }
        if ($order) {
            $query .= ' ORDER BY ' . $order;
        }
        if ($limit) {
            $query .= ' LIMIT ' . $limit;
        }
        $this->query = $query;
        $this->result = mysqli_query($this->mysql, $query);
        if (mysqli_error($this->mysql) != '') {
            $this->result = null;
            throw new DatabaseException(mysqli_error($this->mysql));
        }
        return $this;
    }
}
