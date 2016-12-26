<?php

namespace Awjudd\PDO\Database;

use PDO;
use ArrayAccess;
use InvalidArgumentException;

/**
 * The handshake between each of the objects.  This holds all
 * of the information one will need to build and run the query.
 *
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
class Query implements ArrayAccess
{
    /**
     * The query which will be run.
     *
     * @var string
     */
    public $query = null;

    /**
     * An array of parameters which will be sent into the query.
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Will hold an error if something went wrong while executing the query.
     *
     * @var Exception
     */
    public $exception = null;

    /**
     * TRUE if the query is successful, FALSE otherwise.
     *
     * @var bool
     */
    public $success = false;

    /**
     * The number of rows returned by the query.
     *
     * @var int
     */
    public $numberOfRows = null;

    /**
     * The inserted ID result from the query.
     *
     * @var int
     */
    public $insertId = null;

    /**
     * The query as passed in by the user.
     *
     * @var string
     */
    public $originalQuery = null;

    /**
     * The parameters as they were passed in.
     *
     * @var mixed
     */
    public $originalParameters = null;

    /**
     * The database statement which was executed.
     *
     * @var PDOStatement
     */
    public $statement = null;

    /**
     * The result set from past retrievals.
     */
    private $results = array();

    /**
     * The configuration as set by the user.
     */
    public $configuration = null;

    /**
     * Used internally to keep track of the row number that we are currently on.
     */
    private $rowNumber = 0;

    /**
     * An array of variables will map to each other.
     *
     * @var array
     */
    private $aliasedVariables = array(
        'n' => 'numberOfRows',
        's' => 'success',
        'ex' => 'exception',
        'id' => 'insertIds',
    );

    /**
     * An array of all methods that will map to each other.
     *
     * @var array
     */
    private $aliasedFunctions = array(
        'arr' => 'getArray',
        'all' => 'retrieveAllRows',
    );

    /**
     * This function is used in order to retrieve a single row from a result set.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the caller
     *
     * @return array An array containing a single row from the caller's query
     */
    public function getArray($fetchStyle = PDO::FETCH_ASSOC, $orientation = PDO::FETCH_ORI_NEXT, $offset = 1)
    {
        if ($orientation === PDO::FETCH_ORI_NEXT) {
            ++$this->rowNumber;
        }

        return $this->statement->fetch($fetchStyle, $orientation, $offset);
    }

    /**
     * This function is used in order to retrieve all rows from a result set.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the caller
     *
     * @return array An array containing all rows from the caller's query
     */
    public function retrieveAllRows($fetchStyle = PDO::FETCH_ASSOC)
    {
        return $this->statement->fetchAll($fetchStyle);
    }

    /**
     * This function is used in order to clean up the results.
     */
    public function freeResults()
    {
        // Close the cursor
        $this->statement->closeCursor();
    }

    public function offsetExists($offset)
    {
        // Check if the offset is numeric
        if ((int) ($offset) == $offset) {
            // It is, so look to see if the offset is within the range
            return $this->numberOfRows >= $offset;
        }

        // It wasn't an integer, so try to grab it from the first element
        if ($this->numberOfRows > 0) {
            return isset($this[0][$offset]);
        }

        // Otherwise it doesn't exist
        return false;
    }

    public function offsetGet($offset)
    {
        // Check if the offset is numeric
        if ((int) ($offset) === $offset) {
            // Check if we already grabbed it
            if (isset($this->result[$offset])) {
                return $this->result[$offset];
            }

            // There are active bugs in mysql and sqlite that don't allwo the cursors to work
            // so, we will need to cycle through each one until the offset to get the one we need.
            if (in_array(strtolower($this->configuration->engine), array('mysql', 'sqlite'))) {
                for ($x = $this->rowNumber; $x <= $offset; ++$x) {
                    $this->results[$x] = $this->getArray(PDO::FETCH_ASSOC);
                }

                return $this->results[$offset];
            }

            // Grab the value that is associated with the offset
            return $this->results[$offset] = $this->getArray(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $offset);
        }

        // It wasn't an integer, so try to grab it from the first element
        if ($this->numberOfRows > 0) {
            $this->results[0] = $this[0];

            return $this->results[0][$offset];
        }

        // Otherwise it doesn't exist
        return false;
    }

    public function offsetSet($offset, $value)
    {
        // This object is read-only, so throw an exception if called
        throw new Exception('This object is read-only.');
    }

    public function offsetUnset($offset)
    {
        // Check if the value exists
        if (isset($this->results[$offset])) {
            // Clear the offset value
            unset($this->results[$offset]);
        }
    }

    /**
     * Overrides the default getter in order to allow for aliasing of some
     * variables.
     *
     * @var The key value that we will be using to look up in the alias array
     */
    public function __get($key)
    {
        // Check if the aliased variable exists
        if (isset($this->aliasedVariables[$key])) {
            // Return the alaised value
            return $this->{$this->aliasedVariables[$key]};
        }
    }

    /**
     * Overrides the default call method, in order allow for aliasing of functions.
     *
     * @var The         method that we will be calling
     * @var $parameters The parameters that were passed into the function
     */
    public function __call($method, $parameters)
    {
        // Verify that the method exists
        if(!in_array($method, $this->aliasedFunctions)) {
            // It doesn't, so throw an exception
            throw new InvalidArgumentException("Unknown method $method requested");
        }

        // Call the method that we are aliasing
        return call_user_func_array(array($this, $this->aliasedFunctions[$method]), $parameters);
    }
}