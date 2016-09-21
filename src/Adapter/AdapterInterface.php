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
namespace Pop\Db\Adapter;

/**
 * Db adapter interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
interface AdapterInterface
{

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return AdapterInterface
     */
    public function query($sql);

    /**
     * Prepare a SQL query.
     *
     * @param  string $sql
     * @return AdapterInterface
     */
    public function prepare($sql);

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return AdapterInterface
     */
    public function bindParams(array $params);

    /**
     * Execute a prepared SQL query
     *
     * @return AdapterInterface
     */
    public function execute();

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    public function fetch();

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll();

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection();

    /**
     * Determine whether or not a statement resource exists
     *
     * @return boolean
     */
    public function hasStatement();

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement();

    /**
     * Determine whether or not a result resource exists
     *
     * @return boolean
     */
    public function hasResult();

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Determine whether or not there is an error
     *
     * @return boolean
     */
    public function hasError();

    /**
     * Set the error
     *
     * @param  string $error
     * @return AdapterInterface
     */
    public function setError($error);

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError();

    /**
     * Throw a database error exception
     *
     * @throws Exception
     * @return void
     */
    public function throwError();

    /**
     * Clear the error
     *
     * @return AdapterInterface
     */
    public function clearError();

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect();

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows();

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables();

}