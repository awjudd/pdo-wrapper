<?php

namespace Awjudd\PDO;

use PDO;
use Closure;
use Exception;
use OutOfBoundsException;
use InvalidArgumentException;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Database\LogEntry;
use Awjudd\PDO\Database\ValueType;
use Awjudd\PDO\Database\Configuration;

/**
 * Database class for all database queries.
 *
 * This database class is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 3 of the License,
 * or (at your option) any later version.
 *
 * This database class is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE.  See the GNU General Public
 * License for more details.
 *
 *
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2017
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 *
 * For full documentation and updates please visit:
 * http://development.andrewjudd.ca
 */
class Database
{
    /**
     * An instance of the database object if used statically.
     *
     * @var        Database
     */
    private static $instance = null;

    /**
     * An instance of the Configuration object which will be used internally
     * to retrieve any settings as specified by the user.
     *
     * @var Configuration
     */
    private $config = null;

    /**
     * Where all of the query logging information will be stored.
     *
     * @var array<LogEntry>
     */
    private $log = array();

    /**
     * Logs the total amount of time spent by the database object.
     *
     * @var int
     */
    private $totalTime = 0;

    /**
     * How many queries have been run?
     *
     * @var int
     */
    private $queryCount = 0;

    /**
     * The connection to the database.
     *
     * @var PDO
     */
    private $connection = null;

    /**
     * The hook that happens before the database query is executed.
     * 
     * @var        Closure
     */
    private $beforeHook = null;

    /**
     * The hook that happens after the database query is executed.
     *
     * @var        Closure
     */
    private $afterHook = null;

    /**
     * An array of all methods that will map to each other.
     *
     * @var array
     */
    private $aliasedFunctions = [
        'q' => 'query',
    ];

    /**
     * The constructor for the database object this will be used in order to set
     * any desired configuration settings and then it will attempt to make a
     * connection to the database.
     *
     * @param Configuration $config An object which contains all of the
     *                                      configuration settings for the instance
     */
    public function __construct(Configuration $config)
    {
        // Set the configuration object
        $this->config = $config;

        // Try to connect to the database
        $this->__connect();
    }

    /**
     * Creates/retrieves a static instance of the database object
     *
     * @param      \Awjudd\PDO\Database\Configuration  $config  The configuration
     *
     * @return     \Awjudd\PDO\Database                         The instance.
     */
    public static function getInstance(Configuration $config = null)
    {
        if(is_null(static::$instance)) {
            static::$instance = new Database($config);
        }

        return static::$instance;
    }

    /**
     * This function is the starting function for the query that is being run.
     * It will take in the query with the following values for the parameters
     * and then insert the values in their corresponding spots after making
     * sure that the data passed in matches the correct data type (based on
     * regular expression).
     *
     * @throws InvalidArgumentException If no query is provided, an InvalidArgumentException will be thrown
     *
     * @internal param mixed $args1 -X All of the corresponding values for the query
     * (the number of these is based on however many parameters there are in
     * the database query)
     *
     * @return Query An object which contains all information about the
     *                       query which was just executed
     */
    public function query()
    {
        try {
            // Grab all of the parameters to the function
            $query = func_get_args();

            // If there are none, then throw an exception
            if (func_num_args() === 0) {
                throw new InvalidArgumentException('No query provided.');
            }

            // Are we a wrapped array?
            if(is_array($query[0])) {
                $query = $query[0];
            }

            // The query is valid, so parse it
            $parsedQuery = $this->__parse($query);

            // Run the query based on the parsed query
            $this->__run($parsedQuery);

            // Return the hydrated Query object
            return $parsedQuery;
        } catch (InvalidArgumentException $e) {
            $this->__error($e);
        }

        // Return NULL so there is an issue that is known / capturable
        return null;
    }

    /**
     * Used in order to return the instance of the PDO object.
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Retrieves the query execution log.
     *
     * @return array<LogEntry>
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Retrieves the number of queries which have been executed.
     *
     * @return int
     */
    public function getQueryCount()
    {
        return $this->queryCount;
    }

    /**
     * Retrieves the total time it took to process all requests
     * for the database object.
     *
     * @return int
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * This function is used in order to start a transaction.
     */
    public function startTransaction()
    {
        try {
            $this->connection->beginTransaction();
        } catch (PDOException $e) {
            $this->__error($e);
        }
    }

    /**
     * This function is used in order to commit a transaction.
     */
    public function commitTransaction()
    {
        try {
            $this->connection->commit();
        } catch (PDOException $e) {
            $this->__error($e);
        }
    }

    /**
     * This function is used in order to roll back a transaction.
     */
    public function rollbackTransaction()
    {
        try {
            $this->connection->rollback();
        } catch (PDOException $e) {
            $this->__error($e);
        }
    }

    /**
     * This function is used in order to connect to the database.
     */
    private function __connect()
    {
        // Try to connect to the database
        try {
            $start = microtime();

            // Build the dns
            $dns = $this->config->getConnectionString();

            // Establish the connection
            $this->connection = new PDO($dns, $this->config->username, $this->config->password);

            // The amount of time it took to connect to the database
            $length = (int)microtime() - (int)$start;

            // Add the time spent trying to connect to the database
            $this->__addTime($length);

            // Add it to the log
            $this->__addToLog('Connection Created', $length, null);

            // Throw an exception whenever there is a problem
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->__error($e);
        }
    }

    /**
     * This function is used in order to parse the query and put it into a common
     * type.
     *
     * @param array $query The query and any parameters
     *
     * @throws Exception|null
     * @throws InvalidArgumentException
     *
     * @return Query A hydrated object with all of the database query information
     *                       prepared
     */
    private function __parse(array $query)
    {
        // The object to be returned after hydrating
        $parsedQuery = null;

        // Clean the query
        $query[0] = trim($query[0]);

        if(preg_match('/;$/', $query[0])) {
            $query[0] = substr($query[0], 0, strlen($query[0]) - 1);
        }

        // The first element of the array will be the query
        switch ($this->config->queryMode) {
            case Configuration::QUERY_CLASSIC:
                $parsedQuery = $this->__parseClassic($query);
                break;
            default:
                $parsedQuery = $this->__parseBasic($query);
                break;
        }

        // Check if the parsed query was built
        if ($parsedQuery === null || !($parsedQuery instanceof Query)) {
            // No parsed query means no query to run
            throw new InvalidArgumentException('The query was not parsed');
        }
        // Check if there were any errors while parsing the query
        elseif ($parsedQuery->exception !== null) {
            // There was, so rethrow it
            throw $parsedQuery->exception;
        }

        return $parsedQuery;
    }

    /**
     * This function is used internally in order to parse the query if provided
     * in the new format.
     *
     * @param array $query The query and any parameters
     *
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     *
     * @return Query The hydrated database query object
     */
    private function __parseBasic(array $query)
    {
        // The object to hydrate
        $obj = new Query();

        // Whether or not the user is defining the location
        $locationBinding = false;

        // Whether or not the user is using inferred locations
        $inferredBinding = false;

        // Set the original query to the first value of the array
        $obj->query = $obj->originalQuery = $query[0];

        // Grab all of the original parameters and attach them to the object
        $obj->originalParameters = array_slice($query, 1);

        // Parse the query looking for /{(((\w+):)?(\w+))}/
        preg_match_all('/{(((\w+):)?(\w+))}/', $query[0], $matches, PREG_SET_ORDER);

        // The list of distinct parameters we'll be using
        $parameters = array();

        // Cycle through each of the matches setting up the query
        foreach ($matches as $match) {
            // The actual parameter number
            $parameterNumber = -1;

            // Check if the user defined a parameter mapping
            if (isset($match[3]) && $match[3] !== '') {
                // Using location binding
                $locationBinding = true;

                // Check if there is inferred binding as well
                if ($inferredBinding) {
                    throw new InvalidArgumentException('You cannot have both inferred, and location binding in the same query.');
                }

                // Verify that the location is numeric
                if (!is_numeric($match[3])) {
                    throw new InvalidArgumentException('Invalid parameter number value ('.$match[3].').');
                }

                // Set the parameter number
                $parameterNumber = $match[3];
            } else {
                // Using inferred binding
                $inferredBinding = true;

                // Check if there is inferred binding as well
                if ($locationBinding) {
                    throw new InvalidArgumentException('You cannot have both inferred, and location binding in the same query.');
                }

                // Grab the next parameter number and bind it
                $parameterNumber = count($parameters);
            }

            // Verify that the parameter exists
            if (!isset($obj->originalParameters[$parameterNumber])) {
                // It wasn't provided, so give an error
                throw new OutOfBoundsException('The specified parameter location is invalid ('.$parameterNumber.').');
            }

            // Verify the specific value in the query
            $this->__verifyType($match[4], $obj->originalParameters[$parameterNumber]);

            // Build the replacement string
            $replace = $this->__replaceString($match[4], $obj->originalParameters[$parameterNumber]);

            // Replace the actual value in the string
            $obj->query = preg_replace('/'.$match[0].'/', $replace['query'], $obj->query, 1);

            // Get the data type
            $type = $this->__getDataTypeCode($match[4]);

            // Add the parameters in
            foreach ($replace['parameters'] as $parameter) {
                // Bind the parameters
                $obj->parameters[] = array('value' => $parameter, 'type' => $type);
            }

            // Add it to the query and the parameter mapping
            $parameters[$parameterNumber] = $obj->originalParameters[$parameterNumber];
        }

        return $obj;
    }

    /**
     * This function is used internally in order to parse the query if provided
     * in the classic format.
     *
     * @param array $query The query and any parameters
     *
     * @throws OutOfBoundsException
     *
     * @return Query The hydrated database query object
     */
    private function __parseClassic(array $query)
    {
        // The object to hydrate
        $obj = new Query();

        // Set the original query to the first value of the array
        $obj->query = $obj->originalQuery = $query[0];

        // Grab all of the original parameters and attach them to the object
        $obj->originalParameters = array_slice($query, 1);

        // Parse the query looking for /%\w+/
        preg_match_all('/%(\w+)/', $query[0], $matches, PREG_SET_ORDER);

        // The list of distinct parameters we'll be using
        $parameters = array();

        // Cycle through each of the matches setting up the query
        foreach ($matches as $match) {
            // Grab the next parameter number and bind it
            $parameterNumber = count($parameters);

            // Verify that the parameter exists
            if (!isset($obj->originalParameters[$parameterNumber])) {
                // It wasn't provided, so give an error
                throw new OutOfBoundsException('The specified parameter location is invalid.');
            }

            // Verify the specific value in the query
            $this->__verifyType($match[1], $obj->originalParameters[$parameterNumber]);

            // Build the replacement string
            $replace = $this->__replaceString($match[1], $obj->originalParameters[$parameterNumber]);

            // Replace the actual value in the string
            $obj->query = preg_replace('/'.$match[0].'/', $replace['query'], $obj->query, 1);

            // Get the data type
            $type = $this->__getDataTypeCode($match[1]);

            // Add the parameters in
            foreach ($replace['parameters'] as $parameter) {
                // Bind the parameters
                $obj->parameters[] = array('value' => $parameter, 'type' => $type);
            }

            // Add it to the query and the parameter mapping
            $parameters[$parameterNumber] = $obj->originalParameters[$parameterNumber];
        }

        return $obj;
    }

    /**
     * This function is used in order to verify the data type coming in.
     *
     * @param string $typecode The type code
     * @param mixed  $value    The value we are comparing
     * @param bool   $isList   Whether or not we are verifying a list's elements (defaults to FALSE)
     *
     * @throws InvalidArgumentException If the data type doesn't match the requirements
     */
    private function __verifyType($typecode, $value, $isList = false)
    {
        // The regular expression to compare against
        $regex = null;

        // The data type
        $type = null;

        switch (strtolower($typecode)) {
            case ValueType::$SIGNED_INTEGER:
                $regex .= '/^[-+]?[0-9]+$/';
                $type = 'integer';
                break;
            case ValueType::$UNSIGNED_INTEGER:
                $regex = '/^[0-9]+$/';
                $type = 'unsigned integer';
                break;
            case ValueType::$SIGNED_DECIMAL:
                $regex .= '/^[-+]?[0-9]+(\.[0-9]+)?$/';
                $type = 'decimal';
                break;
            case ValueType::$UNSIGNED_DECIMAL:
                $regex = '/^[0-9]+(\.[0-9]+)?$/';
                $type = 'unsigned decimal';
                break;
            // Handle the lists
            case ValueType::$VALUE_LIST:
            case ValueType::$VALUE_LIST_SIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_SIGNED_INTEGER:
            case ValueType::$VALUE_LIST_UNSIGNED_INTEGER:
            case ValueType::$VALUE_LIST_STRING:
            case ValueType::$VALUE_LIST_ESCAPED_STRING:
                // Grab the list of elements
                $values = is_array($value) ? $value : explode(',', $value);

                // Figure out which data type the values should be
                switch (strtolower($typecode)) {
                    case ValueType::$VALUE_LIST:
                    case ValueType::$VALUE_LIST_STRING:
                        $typecode = ValueType::$STRING;
                        break;
                    case ValueType::$VALUE_LIST_SIGNED_DECIMAL:
                        $typecode = ValueType::$SIGNED_DECIMAL;
                        break;
                    case ValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
                        $typecode = ValueType::$UNSIGNED_DECIMAL;
                        break;
                    case ValueType::$VALUE_LIST_SIGNED_INTEGER:
                        $typecode = ValueType::$SIGNED_INTEGER;
                        break;
                    case ValueType::$VALUE_LIST_UNSIGNED_INTEGER:
                        $typecode = ValueType::$UNSIGNED_INTEGER;
                        break;
                    case ValueType::$VALUE_LIST_ESCAPED_STRING:
                        $typecode = ValueType::$ESCAPED_STRING;
                        break;
                }

                // Cycle through all of the values
                foreach ($values as $val) {
                    // Verify the type
                    $this->__verifyType($typecode, $val, true);
                }

                break;
            case ValueType::$STRING:
            case ValueType::$ESCAPED_STRING:
                // Do nothing
                break;
            default:
                // We don't match any of the types, so invalid type
                throw new InvalidArgumentException('The data type "'.$typecode.'" is invalid.');
                break;
        }

        // Check if we are comparing a list value
        if ($isList) {
            // We are so include that in the type
            $type .= ' list value';
        }

        // Check if we have a regex being built
        if ($regex !== null) {
            // Try to match it
            if (!preg_match($regex, $value)) {
                // It doesn't match, so throw an exception
                throw new InvalidArgumentException('Invalid data for a "'.$type.'" parameter.');
            }
        }
    }

    /**
     * This function is used internally in order to determine the actual type code
     * of the data.
     *
     * @param string $typecode The type code that we current have
     *
     * @throws InvalidArgumentException
     *
     * @return string The actual data type which is being expected
     */
    private function __getDataTypeCode($typecode)
    {
        switch (strtolower($typecode)) {
            case ValueType::$SIGNED_INTEGER:
            case ValueType::$VALUE_LIST_SIGNED_INTEGER:
                $typecode = ValueType::$SIGNED_INTEGER;
                break;
            case ValueType::$UNSIGNED_INTEGER:
            case ValueType::$VALUE_LIST_UNSIGNED_INTEGER:
                $typecode = ValueType::$UNSIGNED_INTEGER;
                break;
            case ValueType::$SIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_SIGNED_DECIMAL:
                $typecode = ValueType::$SIGNED_DECIMAL;
                break;
            case ValueType::$UNSIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
                $typecode = ValueType::$UNSIGNED_DECIMAL;
                break;
            case ValueType::$STRING:
            case ValueType::$VALUE_LIST:
            case ValueType::$VALUE_LIST_STRING:
                $typecode = ValueType::$STRING;
                break;
            case ValueType::$ESCAPED_STRING:
            case ValueType::$VALUE_LIST_ESCAPED_STRING:
                $typecode = ValueType::$ESCAPED_STRING;
                break;
            default:
                // We don't match any of the types, so invalid type
                throw new InvalidArgumentException('The data type "'.$typecode.'" is invalid.');
                break;
        }

        return $typecode;
    }

    /**
     * This function is used in order to build the replacement string for the
     * parameter.
     *
     * @param string $typecode The type code
     * @param mixed  $value    The value we are binding
     *
     * @return array { 'query' => The change to the query which will be added in
     *               , 'parameter' => The parameters to be bound }
     */
    private function __replaceString($typecode, $value)
    {
        // The array which we'll be returning
        $return = array();

        switch (strtolower($typecode)) {
            // Handle the lists
            case ValueType::$VALUE_LIST:
            case ValueType::$VALUE_LIST_SIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
            case ValueType::$VALUE_LIST_SIGNED_INTEGER:
            case ValueType::$VALUE_LIST_UNSIGNED_INTEGER:
            case ValueType::$VALUE_LIST_STRING:
            case ValueType::$VALUE_LIST_ESCAPED_STRING:
                // Grab the list of elements
                $values = is_array($value) ? $value : explode(',', $value);

                // Calculate the total number of elements
                $count = count($values);

                // Build the string and bind all of the elements
                $return['query'] = '('.substr(str_repeat('?,', $count), 0, $count * 2 - 1).')';
                $return['parameters'] = $values;
                break;
            // For the majority of the types we are just returning one value to
            // bind to the query
            default:
                $return['query'] = '?';
                $return['parameters'] = array($value);
                break;
        }

        return $return;
    }

    /**
     * This function is used internally in order to run the query once it was
     * converted to the common form.
     *
     * @param Query $query The query information in a common structure
     */
    private function __run(Query $query)
    {
        // Start logging
        $start = microtime();

        if($this->beforeHook !== null) {
            $hook = $this->beforeHook;
            $hook($query);
        }

        try {
            // Prepare the statement
            $statement = $this->connection->prepare($query->query);

            // Bind the parameters
            $parameterCount = count($query->parameters);

            for ($x = 1; $x <= $parameterCount; ++$x) {
                $parameter = $query->parameters[$x - 1];

                switch ($parameter['type']) {
                    case ValueType::$BINARY:
                        // Attach the blob
                        $statement->bindParam($x, $parameter['value'], PDO::PARAM_LOB);
                        break;
                    case ValueType::$SIGNED_INTEGER:
                    case ValueType::$UNSIGNED_INTEGER:
                        $statement->bindParam($x, $parameter['value'], PDO::PARAM_INT);
                        break;
                    case ValueType::$ESCAPED_STRING:
                        // Update the value so it is escaped, then fall through
                        $parameter['value'] = htmlentities($parameter['value']);
                        $statement->bindParam($x, $parameter['value'], PDO::PARAM_STR, strlen($parameter['value']));
                        break;
                    default:
                        // Otherwise, Just bind it
                        $statement->bindParam($x, $parameter['value'], PDO::PARAM_STR, strlen($parameter['value']));
                }
            }

            // Execute the statement
            $statement->execute();

            $query->pdo = $this;

            // Bind the statement to the object
            $query->statement = $statement;

            // Find out the number of rows
            $query->numberOfRows = $query->deriveRowCount();

            // Find out the last insert id
            $query->insertId = $this->connection->lastInsertId();

            // Give the object access to the configuration
            $query->configuration = $this->config;

            // Add 1 to the query count
            ++$this->queryCount;
        } catch (PDOException $e) {
            // Calculate the duration of the query
            $duration = (int)microtime() - (int)$start;

            // Add it to the log
            $this->__addToLog($e->getMessage(), $duration, $query->originalQuery);

            // Set the exception and that the query failed
            $query->exception = $e;
            $query->success = false;

            // Decide what to do with the error
            $this->__error($e);
        }
        finally {
            if($this->afterHook !== null) {
                $hook = $this->afterHook;
                $hook($query);
            }
        }

        // Calculate the duration of the query
        $duration = (int)microtime() - (int)$start;

        // Determine whether or not the query is successful (based on the exception)
        $query->success = $query->exception === null;

        // Add it to the log
        $this->__addToLog(null, $duration, $query->originalQuery);
    }

    /**
     * This function is used internally in order to add more time to the
     * internal clock to determine how long each query lasts.
     *
     * @param int $duration The number of milliseconds that the processing took
     */
    private function __addTime($duration)
    {
        $this->totalTime += $duration;
    }

    /**
     * This function is used internally in order to add a new message to the log.
     *
     * @param string $message  The message to add to the log
     * @param int    $duration The time in milliseconds that were spent on this
     *                         action
     * @param string $query    The query which was executed
     */
    private function __addToLog($message, $duration, $query)
    {
        // Add it to the total time
        $this->__addTime($duration);

        // Check if logging is enabled
        if ($this->config->maintainQueryLog) {
            // Create the log object
            $entry = new LogEntry();

            // Hydrate the object
            $entry->message = $message;
            $entry->duration = $duration;
            $entry->query = $query;
            $entry->backtrace = debug_backtrace();

            // Push it on the end of the array
            $this->log[] = $entry;
        }
    }

    /**
     * This function is used internally in order to handle any errors which
     * come up through use of the database object.
     *
     * @param Exception $exception The exception which was thrown
     *
     * @throws Exception
     *
     * @return
     */
    private function __error(Exception $exception)
    {
        // Check the logging level
        if ($this->config->errorReporting === Configuration::ERRORS_IGNORE) {
            // Ignoring all issues, so just leave
            return;
        }

        // Grab the stack trace
        $trace = debug_backtrace();

        // Holds the final frame
        $debug_frame = null;

        // Find the first non-database class error
        foreach ($trace as $frame) {
            if (!isset($frame['file']) || $frame['file'] !== __FILE__) {
                $debug_frame = $frame;
                break;
            }
        }

        // Build the error message
        $message = $exception->getMessage()
            .PHP_EOL.'File: '.$debug_frame['file']
            .PHP_EOL.'Line Number: '.$debug_frame['line'];

        // Check if they selected to echo the error message
        if (($this->config->errorReporting & Configuration::ERRORS_ECHO)
            === Configuration::ERRORS_ECHO) {
            // They chose to echo the errors, so echo it
            echo $message;
        }

        if (($this->config->errorReporting & Configuration::ERRORS_LOGFILE)
            === Configuration::ERRORS_LOGFILE) {
            // Throw the results at the end of the log file
            file_put_contents($this->config->errorLogFile, $message.PHP_EOL, FILE_APPEND);
        }

        if (($this->config->errorReporting & Configuration::ERRORS_EXCEPTION)
            === Configuration::ERRORS_EXCEPTION) {
            // Rethrow the exception
            throw $exception;
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
            // It doesn't, so give an error
            throw new InvalidArgumentException("Unknown method $method requested");
        }

        // Call the method that we are aliasing
        return call_user_func_array(array($this, $this->aliasedFunctions[$method]), $parameters);
    }

    /**
     * Gets the An array of all methods that will map to each other.
     *
     * @return array
     */
    public function getAliasedFunctions()
    {
        return $this->aliasedFunctions;
    }

    /**
     * Sets the An array of all methods that will map to each other.
     *
     * @param array $aliasedFunctions the aliased functions
     *
     * @return self
     */
    private function _setAliasedFunctions(array $aliasedFunctions)
    {
        $this->aliasedFunctions = $aliasedFunctions;

        return $this;
    }

    /**
     * Sets the The hook that happens before the database query is executed.
     *
     * @param        Closure $beforeHook the before hook
     *
     * @return self
     */
    public function setBeforeHook(Closure $beforeHook)
    {
        $this->beforeHook = $beforeHook;

        return $this;
    }

    /**
     * Sets the The hook that happens after the database query is executed.
     *
     * @param        Closure $afterHook the after hook
     *
     * @return self
     */
    public function setAfterHook(Closure $afterHook)
    {
        $this->afterHook = $afterHook;

        return $this;
    }
}

