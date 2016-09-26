<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Gateway;
use Pop\Db\Parser;
use Pop\Db\Sql;

/**
 * Result class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Result implements \ArrayAccess
{

    /**
     * Data set result constants
     * @var string
     */
    const AS_ARRAY  = 'AS_ARRAY';
    const AS_OBJECT = 'AS_OBJECT';
    const AS_RESULT = 'AS_RESULT';

    /**
     * Database connection
     * @var AbstractAdapter
     */
    protected $db = null;

    /**
     * SQL Object
     * @var Sql
     */
    protected $sql = null;

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Row gateway
     * @var Gateway\Row
     */
    protected $rowGateway = null;

    /**
     * Table gateway
     * @var Gateway\Table
     */
    protected $tableGateway = null;

    /**
     * Column names of the table
     * @var array
     */
    protected $columnNames = [];

    /**
     * Columns of the first result row
     * @var string
     */
    protected $columns = [];

    /**
     * Result rows
     * @var array
     */
    protected $rows = [];

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Is new record flag
     * @var boolean
     */
    protected $isNew = false;

    /**
     * Constructor
     *
     * Instantiate the database record result object
     *
     * @param  AbstractAdapter $db
     * @param  string          $table
     * @param  mixed           $keys
     * @param  array           $columns
     */
    public function __construct(AbstractAdapter $db, $table, $keys = null, array $columns = null)
    {
        $this->db           = $db;
        $this->sql          = $db->createSql();
        $this->table        = $table;
        $this->tableGateway = new Gateway\Table($this->sql, $this->table);

        if (null !== $columns) {
            $this->isNew = true;
            $this->setColumns($columns);
        }

        $tableInfo         = $this->getTableInfo();
        $this->columnNames = array_keys($tableInfo['columns']);

        if (null !== $keys) {
            $keys = (!is_array($keys)) ? [$keys] : $keys;
        } else {
            $keys = [];
            foreach ($tableInfo['columns'] as $column => $info) {
                if ($info['primary']) {
                    $keys[] = $column;
                }
            }
        }

        $this->primaryKeys = $keys;
        $this->rowGateway  = new Gateway\Row($this->sql, $this->table, $this->primaryKeys);
    }

    /**
     * Get the DB adapter
     *
     * @return AbstractAdapter
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Get the SQL object
     *
     * @return Sql
     */
    public function sql()
    {
        return $this->sql;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @return int
     */
    public function getTotal(array $columns = null)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $rows = $this->tableGateway->select(['total_count' => 'COUNT(1)'], $where, $params);


        return (isset($rows[0]) && isset($rows[0]['total_count'])) ? (int)$rows[0]['total_count'] : 0;
    }

    /**
     * Get table info and return as an array
     *
     * @return array
     */
    public function getTableInfo()
    {
        return $this->tableGateway->getTableInfo();
    }

    /**
     * Find record by ID method
     *
     * @param  mixed $id
     * @return Result
     */
    public function findById($id)
    {
        $this->setColumns($this->rowGateway->find($id));
        return $this;
    }

    /**
     * Find records by method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultsAs
     * @return Result
     */
    public function findBy(array $columns = null, array $options = null, $resultsAs = Result::AS_RESULT)
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $this->setRows($this->tableGateway->select(null, $where, $params, $options), $resultsAs);

        return $this;
    }

    /**
     * Find all records method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return Result
     */
    public function findAll(array $options = null, $resultsAs = Result::AS_RESULT)
    {
        return $this->findBy(null, $options, $resultsAs);
    }

    /**
     * Method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $resultsAs
     * @return Result
     */
    public function execute($sql, $params, $resultsAs = Result::AS_RESULT)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }
        if (!is_array($params)) {
            $params = [$params];
        }

        $this->db->prepare($sql)
             ->bindParams($params)
             ->execute();

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $this->db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $row;
            }
            $this->setRows($rows, $resultsAs);
            if (isset($rows[0])){
                $this->setColumns($rows[0]);
            }
        }

        return $this;
    }

    /**
     * Method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultsAs
     * @return Result
     */
    public function query($sql, $resultsAs = Result::AS_RESULT)
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $this->db->query($sql);

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = [];
            while (($row = $this->db->fetch())) {
                $rows[] = $row;
            }
            $this->setRows($rows, $resultsAs);
            if (isset($rows[0])){
                $this->setColumns($rows[0]);
            }
        }

        return $this;
    }

    /**
     * Save the record
     *
     * @param  array $columns
     * @return void
     */
    public function save(array $columns = null)
    {
        // Save or update the record
        if (null === $columns) {
            $this->rowGateway->setColumns($this->columns);
            if ($this->isNew) {
                $this->rowGateway->save();
            } else {
                $this->rowGateway->update();
            }
            $this->setColumns($this->rowGateway->getColumns());
        // Else, save multiple rows
        } else {
            $this->tableGateway->insert($columns);
            $rows = $this->tableGateway->getRows();

            if (isset($rows[0])) {
                $this->setColumns($rows[0]);
            }
        }
    }

    /**
     * Delete the record or rows of records
     *
     * @param  array  $columns
     * @return void
     */
    public function delete(array $columns = null)
    {
        // Delete the record
        if (null === $columns) {
            if ((count($this->columns) > 0) && (count($this->rowGateway->getColumns()) == 0)) {
                $this->rowGateway->setColumns($this->columns);
            }
            $this->rowGateway->delete();
        // Delete multiple rows
        } else {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $this->tableGateway->delete($parsedColumns['where'], $parsedColumns['params']);
        }
        $this->setColumns();
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return Result
     */
    public function setColumns($columns = null)
    {
        $this->columns = [];

        if (null !== $columns) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->columns = (array)$columns;
            } else {
                throw new Exception('The parameter passed must be either an array, an array object or null.');
            }
        }

        return $this;
    }

    /**
     * Set all the table rows at once
     *
     * @param  array  $rows
     * @param  string $resultsAs
     * @return Result
     */
    public function setRows(array $rows = null, $resultsAs = Result::AS_RESULT)
    {
        $this->columns = [];
        $this->rows    = [];

        if (null !== $rows) {
            $this->columns = (isset($rows[0])) ? (array)$rows[0] : [];
            foreach ($rows as $row) {
                switch ($resultsAs) {
                    case self::AS_ARRAY:
                        $this->rows[] = (array)$row;
                        break;
                    case self::AS_OBJECT:
                        $row = (array)$row;
                        foreach ($row as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = new \ArrayObject((array)$v, \ArrayObject::ARRAY_AS_PROPS);
                                }
                                $row[$key] = $value;
                            }
                        }
                        $this->rows[] = new \ArrayObject((array)$row, \ArrayObject::ARRAY_AS_PROPS);
                        break;
                    default:
                        $row = (array)$row;
                        foreach ($row as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = new Result($this->db, $this->table, $this->primaryKeys);
                                    $value[$k]->setColumns((array)$v);
                                }
                                $row[$key] = $value;
                            }
                        }
                        $r = new Result($this->db, $this->table, $this->primaryKeys);
                        $r->setColumns((array)$row);
                        $this->rows[] = $r;
                }
            }
        }

        return $this;
    }

    /**
     * Set 1:1 relationships
     *
     * @param  array $oneToOne
     * @return Result
     */
    public function setOneToOne(array $oneToOne)
    {
        $this->rowGateway->setOneToOne($oneToOne);
        $this->tableGateway->setOneToOne($oneToOne);
        return $this;
    }

    /**
     * Set 1:many relationships
     *
     * @param  array $oneToMany
     * @return Result
     */
    public function setOneToMany(array $oneToMany)
    {
        $this->rowGateway->setOneToMany($oneToMany);
        $this->tableGateway->setOneToMany($oneToMany);
        return $this;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->columns;
    }

    /**
     * Get column values as array object
     *
     * @return \ArrayObject
     */
    public function toArrayObject()
    {
        return new \ArrayObject($this->columns, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get the rows
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the rows (alias method)
     *
     * @return array
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Determine if the result has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return (count($this->rows) > 0);
    }

    /**
     * Magic method to set the property to the value of $this->columns[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->columns[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->columns[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->columns[$name])) ? $this->columns[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->columns[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * Magic method to unset $this->columns[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->columns[$name])) {
            unset($this->columns[$name]);
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}