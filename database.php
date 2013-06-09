<?php
/**
 * Database class for all database queries
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
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @package Database
 * @version 3.0.0
 *
 * For full documentation and updates please visit:
 * http://development.andrewjudd.ca
 */
class Database
{
    /**
     * An instance of the DatabaseConfiguration object which will be used internally
     * to retrieve any settings as specified by the user.
     * @var DatabaseConfiguration
     */
    private $config=NULL;

    /**
     * Where all of the query logging information will be stored.
     * @var array<DatabaseLogEntry>
     */
    private $log=array();

    /**
     * Logs the total amount of time spent by the database object.
     * @var int
     */
    private $totalTime=0;
    
    /**
     * How many queries have been run?
     * @var int
     */
    private $queryCount=0;
    
    /**
     * The connection to the database
     * @var PDO
     */
    private $connection=NULL;

    /**
     * An array of all methods that will map to each other.
     * @var array
     */
    private $aliasedFunctions=array(
        'q' => 'query'
    );

    /**
     * The constructor for the database object this will be used in order to set
     * any desired configuration settings and then it will attempt to make a
     * connection to the database.
     * @param DatabaseConfiguration $config An object which contains all of the
     *        configuration settings for the instance.
     */
    public function __construct(DatabaseConfiguration $config)
    {
        // Set the configuration object
        $this->config=$config;

        // Try to connect to the database
        $this->__connect();
    }

    /**
     * This function is the starting function for the query that is being run.
     * It will take in the query with the following values for the parameters
     * and then insert the values in their corresponding spots after making
     * sure that the data passed in matches the correct data type (based on
     * regular expression).
     * @throws InvalidArgumentException If no query is provided, an InvalidArgumentException will be thrown
     * @internal param mixed $args1 -X All of the corresponding values for the query
     * (the number of these is based on however many parameters there are in
     * the database query)
     * @return DatabaseQuery An object which contains all information about the
     * query which was just executed.
     */
    public function query()
    {
    	try
    	{
    		// Grab all of the parameters to the function
    		$query=func_get_args();
    		
    		// If there are none, then throw an exception
    		if(func_num_args()===0)
    		{
    			throw new InvalidArgumentException("No query provided.");
    		}
    		
    		// The query is valid, so parse it
    		$parsedQuery=$this->__parse($query);
	        
	        // Run the query based on the parsed query
	        $this->__run($parsedQuery);
	        
	        // Return the hydrated DatabaseQuery object
	        return $parsedQuery;
    	}
    	catch(InvalidArgumentException $e)
    	{
    		$this->__error($e);
    	}

        // Return NULL so there is an issue that is known / capturable
        return NULL;
    }
    
    /**
     * Used in order to return the instance of the PDO object.
     * @return PDO
     */
    public function getConnection()
    {
    	return $this->connection;
    }
    
    /**
     * Retrieves the query execution log.
     * @return array<DatabaseLogEntry>
     */
    public function getLog()
    {
    	return $this->log;
    }
    
    /**
     * Retrieves the number of queries which have been executed.
     * @return int
     */
    public function getQueryCount()
    {
    	return $this->queryCount;
    }
    
    /**
     * Retrieves the total time it took to process all requests
     * for the database object.
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
    	try
    	{
    		$this->connection->beginTransaction();
    	}
    	catch(PDOException $e)
    	{
    		$this->__error($e);
    	}
    }
    
    /**
     * This function is used in order to commit a transaction.
     */
    public function commitTransaction()
    {
    	try
    	{
    		$this->connection->commit();
    	}
    	catch(PDOException $e)
    	{
    		$this->__error($e);
    	}
    }
    
    /**
     * This function is used in order to roll back a transaction
     */
    public function rollbackTransaction()
    {
    	try
    	{
    		$this->connection->rollback();
    	}
    	catch(PDOException $e)
    	{
    		$this->__error($e);
    	}
    }
    
    /**
     * This function is used in order to connect to the database
     */
    private function __connect()
    {
        // Try to connect to the database
        try
        {
            $start = microtime();

            // Build the dns
            $dns = $this->config->engine.':host='.$this->config->hostname
            	. ';dbname='.$this->config->database;

            // Establish the connection
            $this->connection = new PDO($dns,$this->config->username, $this->config->password);
                               
            // The amount of time it took to connect to the database
            $length = microtime()-$start;
                               
            // Add the time spent trying to connect to the database
            $this->__addTime($length);
            
            // Add it to the log
            $this->__addToLog("Connection Created", $length, NULL);
            
            // Throw an exception whenever there is a problem
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e)
        {
            $this->__error($e);
        }
    }

    /**
     * This function is used in order to parse the query and put it into a common
     * type.
     * @param array $query The query and any parameters
     * @throws Exception|null
     * @throws InvalidArgumentException
     * @return DatabaseQuery A hydrated object with all of the database query information
     * prepared.
     */
    private function __parse(array $query)
    {
    	// The object to be returned after hydrating
    	$parsedQuery=NULL;
    	
    	// The first element of the array will be the query
    	switch($this->config->queryMode)
    	{
    		case DatabaseConfiguration::QUERY_CLASSIC:
    			$parsedQuery=$this->__parseClassic($query);
    			break;
    		default:
    			$parsedQuery=$this->__parseBasic($query);
    			break;
    	}
    	
    	// Check if the parsed query was built
    	if($parsedQuery===NULL || !($parsedQuery instanceof DatabaseQuery))
    	{
    		// No parsed query means no query to run
    		throw new InvalidArgumentException('The query was not parsed');
    	}
    	// Check if there were any errors while parsing the query
    	elseif($parsedQuery->exception!==NULL)
    	{
    		// There was, so rethrow it
    		throw $parsedQuery->exception;
    	}

    	return $parsedQuery;
    }

    /**
     * This function is used internally in order to parse the query if provided
     * in the new format.
     * @param array $query The query and any parameters
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     * @return DatabaseQuery The hydrated database query object
     */
    private function __parseBasic(array $query)
    {
    	// The object to hydrate
    	$obj=new DatabaseQuery();
    	
    	// Whether or not the user is defining the location
    	$locationBinding=FALSE;
    	
    	// Whether or not the user is using inferred locations
    	$inferredBinding=FALSE;
    	 
    	// Set the original query to the first value of the array
    	$obj->query=$obj->originalQuery=$query[0];
    	
    	// Grab all of the original parameters and attach them to the object
    	$obj->originalParameters=array_slice($query, 1);
    	
    	// Parse the query looking for /{(((\w+):)?(\w+))}/
    	preg_match_all('/{(((\w+):)?(\w+))}/', $query[0], $matches, PREG_SET_ORDER);
    	
    	// The list of distinct parameters we'll be using
    	$parameters=array();
    	
    	// Cycle through each of the matches setting up the query
    	foreach($matches as $match)
    	{
    		// The actual parameter number
    		$parameterNumber=-1;
    	
    		// Check if the user defined a parameter mapping
    		if(isset($match[3]) && $match[3]!=='')
    		{
    			// Using location binding
    			$locationBinding=TRUE;
    			
    			// Check if there is inferred binding as well
    			if($inferredBinding)
    			{
    				throw new InvalidArgumentException('You cannot have both inferred, and location binding in the same query.');
    			}
    			
    			// Verify that the location is numeric
    			if(!is_numeric($match[3]))
    			{
    				throw new InvalidArgumentException('Invalid parameter number value ('.$match[3].').');
    			}
    			
    			// Set the parameter number
    			$parameterNumber=$match[3];
    		}
    		else
    		{
    			// Using inferred binding
    			$inferredBinding=TRUE;
    			 
    			// Check if there is inferred binding as well
    			if($locationBinding)
    			{
    				throw new InvalidArgumentException('You cannot have both inferred, and location binding in the same query.');
    			}
    			
    			// Grab the next parameter number and bind it
    			$parameterNumber=count($parameters);
    		}
    		
    		// Verify that the parameter exists
    		if(!isset($obj->originalParameters[$parameterNumber]))
    		{
    			// It wasn't provided, so give an error
    			throw new OutOfBoundsException('The specified parameter location is invalid (' . $parameterNumber . ').');
    		}
    		
    		// Verify the specific value in the query
    		$this->__verifyType($match[4], $obj->originalParameters[$parameterNumber]);
    	
    		// Build the replacement string
    		$replace=$this->__replaceString($match[4], $obj->originalParameters[$parameterNumber]);
    		
    		// Replace the actual value in the string
    		$obj->query=preg_replace('/'.$match[0].'/', $replace['query'], $obj->query, 1);
    		
    		// Get the data type
    		$type=$this->__getDataTypeCode($match[4]);
    		
    		// Add the parameters in
    		foreach($replace['parameters'] as $parameter)
    		{
    			// Bind the parameters
    			$obj->parameters[]=array('value'=>$parameter, 'type'=>$type);
    		}
    		
    		// Add it to the query and the parameter mapping
    		$parameters[$parameterNumber]=$obj->originalParameters[$parameterNumber];
    	}
    	 
    	return $obj;
    }

    /**
     * This function is used internally in order to parse the query if provided
     * in the classic format.
     * @param array $query The query and any parameters
     * @throws OutOfBoundsException
     * @return DatabaseQuery The hydrated database query object
     */
    private function __parseClassic(array $query)
    {
    	// The object to hydrate
    	$obj=new DatabaseQuery();
    	
    	// Set the original query to the first value of the array
    	$obj->query=$obj->originalQuery=$query[0];
    	 
    	// Grab all of the original parameters and attach them to the object
    	$obj->originalParameters=array_slice($query, 1);
    	 
    	// Parse the query looking for /%\w+/
    	preg_match_all('/%(\w+)/', $query[0], $matches, PREG_SET_ORDER);
    	 
    	// The list of distinct parameters we'll be using
    	$parameters=array();
    	
    	// Cycle through each of the matches setting up the query
    	foreach($matches as $match)
    	{  		
    		// Grab the next parameter number and bind it
    		$parameterNumber=count($parameters);
    	
    		// Verify that the parameter exists
    		if(!isset($obj->originalParameters[$parameterNumber]))
    		{
    			// It wasn't provided, so give an error
    			throw new OutOfBoundsException('The specified parameter location is invalid.');
    		}
    	
    		// Verify the specific value in the query
    		$this->__verifyType($match[1], $obj->originalParameters[$parameterNumber]);
    		 
    		// Build the replacement string
    		$replace=$this->__replaceString($match[1], $obj->originalParameters[$parameterNumber]);
    	
    		// Replace the actual value in the string
    		$obj->query=preg_replace('/'.$match[0].'/', $replace['query'], $obj->query, 1);
    	
    		// Get the data type
    		$type=$this->__getDataTypeCode($match[1]);
    	
    		// Add the parameters in
    		foreach($replace['parameters'] as $parameter)
    		{
    			// Bind the parameters
    			$obj->parameters[]=array('value'=>$parameter, 'type'=>$type);
    		}
    	
    		// Add it to the query and the parameter mapping
    		$parameters[$parameterNumber]=$obj->originalParameters[$parameterNumber];
    	}
    	
    	return $obj;
    }
    
    /**
     * This function is used in order to verify the data type coming in.
     * @param string $typecode The type code 
     * @param mixed $value The value we are comparing
     * @param bool $isList Whether or not we are verifying a list's elements (defaults to FALSE)
     * @throws InvalidArgumentException If the data type doesn't match the requirements
     */
    private function __verifyType($typecode, $value, $isList=FALSE)
    {
    	// The regular expression to compare against
    	$regex=NULL;
    	
    	// The data type
    	$type=NULL;
    	
    	switch(strtolower($typecode))
    	{
    		case DatabaseValueType::$SIGNED_INTEGER:
    			$regex.='/^[-+]?[0-9]+$/';
    			$type='integer';
    			break;
    		case DatabaseValueType::$UNSIGNED_INTEGER:
    			$regex='/^[0-9]+$/';
    			$type='unsigned integer';
    			break;
    		case DatabaseValueType::$SIGNED_DECIMAL:
    			$regex.='/^[-+]?[0-9]+(\.[0-9]+)?$/';
    			$type='decimal';
    			break;
    		case DatabaseValueType::$UNSIGNED_DECIMAL:
    			$regex='/^[0-9]+(\.[0-9]+)?$/';
    			$type='unsigned decimal';
    			break;
    		// Handle the lists
    		case DatabaseValueType::$VALUE_LIST:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_STRING:
    		case DatabaseValueType::$VALUE_LIST_ESCAPED_STRING:
    			// Grab the list of elements
    			$values=is_array($value)?$value:explode(',',$value);
    			
    			// Figure out which data type the values should be
    			switch(strtolower($typecode))
    			{
    				case DatabaseValueType::$VALUE_LIST:
    				case DatabaseValueType::$VALUE_LIST_STRING:
    					$typecode=DatabaseValueType::$STRING;
    					break;
    				case DatabaseValueType::$VALUE_LIST_SIGNED_DECIMAL:
    					$typecode=DatabaseValueType::$SIGNED_DECIMAL;
    					break;
    				case DatabaseValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
    					$typecode=DatabaseValueType::$UNSIGNED_DECIMAL;
    					break;
    				case DatabaseValueType::$VALUE_LIST_SIGNED_INTEGER:
    					$typecode=DatabaseValueType::$SIGNED_INTEGER;
    					break;
    				case DatabaseValueType::$VALUE_LIST_UNSIGNED_INTEGER:
    					$typecode=DatabaseValueType::$UNSIGNED_INTEGER;
    					break;
    				case DatabaseValueType::$VALUE_LIST_ESCAPED_STRING:
    					$typecode=DatabaseValueType::$ESCAPED_STRING;
    					break;
    			}
    			
    			// Cycle through all of the values
    			foreach($values as $val)
    			{
    				// Verify the type
    				$this->__verifyType($typecode, $val, TRUE);
    			}
    			
    			break;
    		case DatabaseValueType::$STRING:
    		case DatabaseValueType::$ESCAPED_STRING:
    			// Do nothing
    			break;
    		default:
    			// We don't match any of the types, so invalid type
    			throw new InvalidArgumentException('The data type "'.$typecode.'" is invalid.');
    			break;
    	}
    	
    	// Check if we are comparing a list value
    	if($isList)
    	{
    		// We are so include that in the type
    		$type.=' list value';
    	}
    	
    	// Check if we have a regex being built
    	if($regex!==NULL)
    	{
    		// Try to match it
    		if(!preg_match($regex, $value))
    		{
    			// It doesn't match, so throw an exception
    			throw new InvalidArgumentException('Invalid data for a "' . $type . '" parameter.');
    		}
    	}
    }

    /**
     * This function is used internally in order to determine the actual type code
     * of the data.
     * @param string $typecode The type code that we current have
     * @throws InvalidArgumentException
     * @return string The actual data type which is being expected.
     */
    private function __getDataTypeCode($typecode)
    {
    	switch(strtolower($typecode))
    	{
    		case DatabaseValueType::$SIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_INTEGER:
    			$typecode=DatabaseValueType::$SIGNED_INTEGER;
    			break;
    		case DatabaseValueType::$UNSIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_INTEGER:
    			$typecode=DatabaseValueType::$UNSIGNED_INTEGER;
    			break;
    		case DatabaseValueType::$SIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_DECIMAL:
    			$typecode=DatabaseValueType::$SIGNED_DECIMAL;
    			break;
    		case DatabaseValueType::$UNSIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
    			$typecode=DatabaseValueType::$UNSIGNED_DECIMAL;
    			break;
    		case DatabaseValueType::$STRING:
    		case DatabaseValueType::$VALUE_LIST:
    		case DatabaseValueType::$VALUE_LIST_STRING:
    			$typecode=DatabaseValueType::$STRING;
    			break;
    		case DatabaseValueType::$ESCAPED_STRING:
    		case DatabaseValueType::$VALUE_LIST_ESCAPED_STRING:
    			$typecode=DatabaseValueType::$ESCAPED_STRING;
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
     * @param string $typecode The type code
     * @param mixed $value The value we are binding
     * @return array { 'query' => The change to the query which will be added in
     *  , 'parameter' => The parameters to be bound }
     */
    private function __replaceString($typecode, $value)
    {
    	// The array which we'll be returning
    	$return = array();
    	
    	switch(strtolower($typecode))
    	{
    		// Handle the lists
    		case DatabaseValueType::$VALUE_LIST:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_DECIMAL:
    		case DatabaseValueType::$VALUE_LIST_SIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_UNSIGNED_INTEGER:
    		case DatabaseValueType::$VALUE_LIST_STRING:
    		case DatabaseValueType::$VALUE_LIST_ESCAPED_STRING: 			
    			// Grab the list of elements
    			$values=is_array($value)?$value:explode(',',$value);
    			
    			// Calculate the total number of elements
    			$count=count($values);
    			
    			// Build the string and bind all of the elements
    			$return['query']='(' . substr(str_repeat('?,', $count),0,$count*2-1) . ')';
    			$return['parameters']=$values;
    			break;
    		// For the majority of the types we are just returning one value to
    		// bind to the query
    		default:
    			$return['query']='?';
    			$return['parameters']=array($value);
    			break;
    	}
    	
    	return $return;
    }
    
    /**
     * This function is used internally in order to run the query once it was
     * converted to the common form.
     * @param DatabaseQuery $query The query information in a common structure.
     */
    private function __run(DatabaseQuery $query)
    {
    	// Start logging
    	$start=microtime();
    	
    	try
    	{
    		// Prepare the statement
    		$statement=$this->connection->prepare($query->query);
    		
    		// Bind the parameters
    		$parameterCount=count($query->parameters);
    		
    		for($x=1;$x<=$parameterCount;++$x)
    		{
    			$parameter=$query->parameters[$x-1];
    			
    			switch ($parameter['type'])
    			{
    				case DatabaseValueType::$BINARY:
    					// Attach the blob
    					$statement->bindParam($x,$parameter['value'], PDO::PARAM_LOB);
                        break;
    				case DatabaseValueType::$SIGNED_INTEGER:
    				case DatabaseValueType::$UNSIGNED_INTEGER:
    					$statement->bindParam($x,$parameter['value'], PDO::PARAM_INT);
    					break;
    				case DatabaseValueType::$ESCAPED_STRING:
    					// Update the value so it is escaped, then fall through
    					$parameter['value']=htmlentities($parameter['value']);
                        $statement->bindParam($x,$parameter['value']
                            , PDO::PARAM_STR,strlen($parameter['value']));
                        break;
    				default:
    					// Otherwise, Just bind it
    					$statement->bindParam($x,$parameter['value']
    						, PDO::PARAM_STR,strlen($parameter['value']));
    			}
    		}
    		
    		// Execute the statement
    		$statement->execute();
    		
    		// Bind the statement to the object
    		$query->statement=$statement;
    		
    		// Find out the number of rows
    		$query->numberOfRows=$statement->rowCount();
    		
    		// Find out the last insert id
    		$query->insertId=$this->connection->lastInsertId();
    		
    		// Add 1 to the query count
    		++$this->queryCount;
    	}
    	catch(PDOException $e)
    	{
    		// Calculate the duration of the query
    		$duration=microtime()-$start;
    		
    		// Add it to the log
    		$this->__addToLog($e->getMessage(),$duration, $query->originalQuery);
    		
    		// Set the exception and that the query failed
    		$query->exception=$e;
    		$query->success=FALSE;
    		
    		// Decide what to do with the error
    		$this->__error($e);
    	}
    	
    	// Calculate the duration of the query
    	$duration=microtime()-$start;
    	
    	// Determine whether or not the query is successful (based on the exception) 
    	$query->success=$query->exception===NULL;
    	
    	// Add it to the log
    	$this->__addToLog(NULL,$duration, $query->originalQuery);
    }

    /**
     * This function is used internally in order to add more time to the
     * internal clock to determine how long each query lasts.
     * @param int $duration The number of milliseconds that the processing took
     */
    private function __addTime($duration)
    {
        $this->totalTime+=$duration;
    }

    /**
     * This function is used internally in order to add a new message to the log.
     * @param string $message The message to add to the log
     * @param int $duration The time in milliseconds that were spent on this
     *       action.
     * @param string $query The query which was executed
     */
    private function __addToLog($message,$duration,$query)
    {
    	// Add it to the total time
    	$this->__addTime($duration);
    	
        // Check if logging is enabled
        if($this->config->maintainQueryLog)
        {
            // Create the log object
            $entry = new DatabaseLogEntry();

            // Hydrate the object
            $entry->message=$message;
            $entry->duration=$duration;
            $entry->query=$query;
            $entry->backtrace=debug_backtrace();

            // Push it on the end of the array
            $this->log[]=$entry;
        }
    }

    /**
     * This function is used internally in order to handle any errors which
     * come up through use of the database object.
     * @param Exception $exception The exception which was thrown
     * @throws Exception
     * @return
     */
    private function __error(Exception $exception)
    {
        // Check the logging level
        if($this->config->errorReporting===DatabaseConfiguration::ERRORS_IGNORE)
        {
            // Ignoring all issues, so just leave
            return;
        }
        
        // Grab the stack trace
        $trace=debug_backtrace();
        
        // Holds the final frame
        $debug_frame=NULL;
        
        // Find the first non-database class error
        foreach($trace as $frame)
        {
        	if(!isset($frame['file'])||$frame['file']!==__FILE__)
        	{
        		$debug_frame=$frame;
        		break;
        	}
        }
        
        // Build the error message
        $message=$exception->getMessage()
        	. PHP_EOL . 'File: ' . $debug_frame['file']
        	. PHP_EOL . 'Line Number: ' . $debug_frame['line'];
        
        // Check if they selected to echo the error message
        if(($this->config->errorReporting & DatabaseConfiguration::ERRORS_ECHO)
            ===DatabaseConfiguration::ERRORS_ECHO)
        {
            // They chose to echo the errors, so echo it
            echo $message;
        }
        
        if(($this->config->errorReporting & DatabaseConfiguration::ERRORS_LOGFILE)
            ===DatabaseConfiguration::ERRORS_LOGFILE)
        {
            // Throw the results at the end of the log file
            file_put_contents($this->config->errorLogFile, $message.PHP_EOL, FILE_APPEND);
        }
        
        if(($this->config->errorReporting & DatabaseConfiguration::ERRORS_EXCEPTION)
            ===DatabaseConfiguration::ERRORS_EXCEPTION)
        {
            // Rethrow the exception
            throw $exception;            
        }
    }

    /**
     * Overrides the default call method, in order allow for aliasing of functions.
     * @var $method The method that we will be calling
     * @var $parameters The parameters that were passed into the function
     */
    public function __call($method, $parameters)
    {
        // Call the method that we are aliasing
        return call_user_func_array(array($this, $this->aliasedFunctions[$method]), $parameters);
    }
}

/**
 * Used to define all of the configuration settings for the database
 * object.
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @package Database
 */
class DatabaseConfiguration
{
    /**
     * Constants defining the degree of error reporting
     */

    /**
     * Ignore any errors
     * @var int
     */
    const ERRORS_IGNORE=0;
    
    /**
     * Echo the error to the screen
     * @var int
     */
    const ERRORS_ECHO=1;
    
    /**
     * Throw an exception when there is an error
     * @var int
     */
    const ERRORS_EXCEPTION=2;
    
    /**
     * Write the exception to a log file
     * @var int
     */
    const ERRORS_LOGFILE=4;
    
    /**
     * Default query parser
     * @var int
     */
    const QUERY_DEFAULT=0;
    
    /**
     * Classic query parser
     * @var int
     */
    const QUERY_CLASSIC=1;

    /**
     * Defines the host name for the database connection.
     * @var string
     */
    public $hostname='localhost';

    /**
     * The database engine to connect to the database with.
     * @var string
     */
    public $engine='mysql';
    
    /**
     * The name of the database which is being used.
     * @var string
     */
    public $database='';

    /**
     * The username used to log into the database server.
     * @var string
     */
    public $username='';

    /**
     * The password used to log into the database server
     * @var string
     */
    public $password='';

    /**
     * Configuration stating how any issues which arise should be handled.
     * @var int
     */
    public $errorReporting=self::ERRORS_EXCEPTION;

    /**
     * Used if the error logging is set to ERROR_LOGFILE.  This will contain the
     * file name that should be written to.
     * @var array
     */
    public $errorLogFile=NULL;

    /**
     * Whether or not a query log should be maintained.
     * @var bool
     */
    public $maintainQueryLog=TRUE;
    
    /**
     * Which parser to use
     * @var int
     */
    public $queryMode=self::QUERY_DEFAULT;
    
    /**
     * This function is used statically in order to make an instance of a database
     * configuration object from an INI file.
     * @param string $iniFile The .ini file path
     * @param string $section The section in the ini file which the database connection
     * information is stored.  Default: NULL, no sections. 
     * @throws Exception::If the file does not exist
     * @throws InvalidArgumentException::If the INI file is invalid
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromINIFile($iniFile, $section=NULL)
    {
        // Check if the file exists
        if(file_exists($iniFile))
        {
            $parseSections=$section!==NULL;
            
            // The file exists, so we'll load it from the file    
            $config=parse_ini_file($iniFile,$parseSections);
            
            // Check if there was an error
            if($config===FALSE)
            {
                throw new InvalidArgumentException('Invalid INI file provided.');
            }
            
            // If there was a section provided, then move to that level
            if($parseSections)
            {
                $config=$config[$section];
            }
            
            // Configure the object
            return self::fromArray($config);
        }
        else
        {
            // Otherwise give an error
            throw new Exception('Configuration file not available');
        }
    }
    
    /**
     * This function is used statically in order to make an instance of a database
     * configuration object from an INI string.
     * @param string $iniString The INI string
     * @param string $section The section in the ini file which the database connection
     * information is stored.  Default: NULL, no sections. 
     * @throws Exception::If the file does not exist
     * @throws InvalidArgumentException::If the INI string is invalid
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromINIString($iniString, $section=NULL)
    {
        // Check if string is empty
        if(!empty($iniString))
        {
            $parseSections=$section!==NULL;
            
            // The file exists, so we'll load it from the file    
            $config=parse_ini_string($iniString,$parseSections);
            
            // Check if there was an error
            if($config===FALSE)
            {
                throw new InvalidArgumentException('Invalid INI string provided.');
            }
            
            // If there was a section provided, then move to that level
            if($parseSections)
            {
                $config=$config[$section];
            }
            
            // Configure the object
            return self::fromArray($config);
        }
        else
        {
            // Otherwise give an error
            throw new Exception('Configuration string not available');
        }
    }

    /**
     * This function is used in order to configure the DatabaseConfiguration
     * object from an array.
     * @param array $config The array of values to configure
     * @throws InvalidArgumentException
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromArray($config)
    {
        $obj=new DatabaseConfiguration();
        
        // Check if the hostname is set
        if(isset($config['Hostname']))
        {
            $obj->hostname=$config['Hostname'];
        }
        
        // Check if the database engine is set
        if(isset($config['Engine']))
        {
            $obj->engine=$config['Engine'];
        }
        
        // Check if the database name is set
        if(isset($config['Database']))
        {
            $obj->database=$config['Database'];
        }
        
        // Check if the username is set
        if(isset($config['Username']))
        {
            $obj->username=$config['Username'];
        }
        
        // Check if the password is set
        if(isset($config['Password']))
        {
            $obj->password=$config['Password'];
        }
        
        // Check if the error reporting level has been set
        if(isset($config['ErrorReporting']))
        {
            $val=$config['ErrorReporting'];
            
            // Calculate the maximum
            $max = self::ERRORS_LOGFILE | self::ERRORS_EXCEPTION | self::ERRORS_ECHO;
            
            // Make sure the value is within the correct range
            if(intval($val)==$val && $val >= self::ERRORS_IGNORE && $val <= $max)
            {
                $obj->errorReporting=$val;
            }
            else
            {
                throw new InvalidArgumentException('Invalid value provided for configuration value "ErrorReporting".');
            }
        }
        
        // Check if the error file has been set
        if(isset($config['ErrorLog']))
        {
            $obj->errorLogFile=$config['ErrorLog'];
        }
        
        // Check if the query log has been set
        if(isset($config['LogQueries']))
        {
            // Check if the value is a string
            if(!is_bool($config['LogQueries']))
            {
                // Check if it is either 'true' or 'false'
                $val = strtolower($config['LogQueries']);
                
                if($val=='true' || $val=='false')
                {
                    // Then assign it as TRUE/FALSE
                    $config['LogQueries']=$val==='true';
                }
                else
                {
                    // Otherwise, invalid so throw an exception
                    throw new InvalidArgumentException('Invalid value provided for configuration value "LogQueries"');
                }
            }
            
            // Make sure the value is a boolean
            if(is_bool($config['LogQueries']))
            {
                $obj->maintainQueryLog=$config['LogQueries'];
            }
        }
        
        // Check if the user wanted to log the errors
        if(($obj->errorReporting & self::ERRORS_LOGFILE)==self::ERRORS_LOGFILE)
        {
            // They wanted a log file, so make sure they provided a name
            if(empty($obj->errorLogFile))
            {
                throw new InvalidArgumentException('Invalid Configuration.  Error file logging, but no error file provided.');
            }
        }
        
        // Check if the query mode has been set
        if(isset($config['QueryMode']))
        {
        	$val=$config['QueryMode'];
        
        	// Calculate the maximum
        	$max = self::QUERY_CLASSIC;
        
        	// Make sure the value is within the correct range
        	if(intval($val)==$val && $val >= self::QUERY_DEFAULT && $val <= $max)
        	{
        		$obj->queryMode=$val;
        	}
        	else
        	{
        		throw new InvalidArgumentException('Invalid value provided for configuration value "QueryMode".');
        	}
        }
        
        // Return the hydrated object
        return $obj;
    }
}

/**
 * Base object used in the logging of database events (if logging is enabled).
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @package Database
 */
class DatabaseLogEntry
{
    /**
     * The number of milliseconds it took to execute the step.
     * @var int
     */
    public $duration=0;

    /**
     * The message for the log.
     * @var string
     */
    public $message=NULL;
    
    /**
     * The query which was run
     * @var DatabaseQuery
     */
    public $query=NULL;
    
    /**
     * The entire stack trace
     * @var array
     */
    public $backtrace=NULL;
    
    /**
     * Overrides the default __toString function to give more valuable
     * information.
     * @return string 
     */
    public function __toString()
    {
    	$string='';
    	
    	// Build the string to return
    	$string.='Query: ' . $this->query . PHP_EOL
    		.'Duration: ' . $this->duration . ' ms' . PHP_EOL;
    	
    	// Check if there is a message
    	if($this->message!==NULL)
    	{
    		// There is, so include it
    		$string.='Message: '.$this->message;
    	}
    	
    	return $string;
    }
}

/**
 * The handshake between each of the objects.  This holds all 
 * of the information one will need to build and run the query.
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @package Database
 */
class DatabaseQuery
{
	/**
	 * The query which will be run.
	 * @var string
	 */
	public $query=NULL;
	
	/**
	 * An array of parameters which will be sent into the query.
	 * @var array
	 */
	public $parameters=array();
	
	/**
	 * Will hold an error if something went wrong while executing the query.
	 * @var Exception
	 */
	public $exception=NULL;
	
	/**
	 * TRUE if the query is successful, FALSE otherwise.
	 * @var bool
	 */
	public $success=FALSE;
	
	/**
	 * The number of rows returned by the query.
	 * @var int
	 */
	public $numberOfRows=NULL;
	
	/**
	 * The inserted ID result from the query.
	 * @var int
	 */
	public $insertId=NULL;
	
	/**
	 * The query as passed in by the user.
	 * @var string
	 */
	public $originalQuery=NULL;
	
	/**
	 * The parameters as they were passed in.
	 * @var mixed
	 */
	public $originalParameters=NULL;
	
	/**
	 * The database statement which was executed.
	 * @var PDOStatement
	 */
	public $statement=NULL;

    /**
     * An array of variables will map to each other.
     * @var array
     */
    private $aliasedVariables=array(
        'n' => 'numberOfRows'
        , 's' => 'success'
        , 'ex' => 'exception'
        , 'id' => 'insertIds'
    );

    /**
     * An array of all methods that will map to each other.
     * @var array
     */
    private $aliasedFunctions=array(
        'arr' => 'getArray'
        , 'all' => 'retrieveAllRows'
    );
	
	/**
	 * This function is used in order to retrieve a single row from a result set.
	 * @param int $fetchStyle Controls how the next row will be returned to the caller.
	 * @return array An array containing a single row from the caller's query
	 */
	public function getArray($fetchStyle=PDO::FETCH_ASSOC)
	{
		return $this->statement->fetch($fetchStyle);
	}
	
	/**
	 * This function is used in order to retrieve all rows from a result set.
	 * @param int $fetchStyle Controls how the next row will be returned to the caller.
	 * @return array An array containing all rows from the caller's query
	 */
	public function retrieveAllRows($fetchStyle=PDO::FETCH_ASSOC)
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

    /**
     * Overrides the default getter in order to allow for aliasing of some
     * variables.
     * @var $key The key value that we will be using to look up in the alias array.
     */
    public function __get($key)
    {
        // Return the value that the aliased key is mapped to
        return $this->{$this->aliasedVariables[$key]};
    }

    /**
     * Overrides the default call method, in order allow for aliasing of functions.
     * @var $method The method that we will be calling
     * @var $parameters The parameters that were passed into the function
     */
    public function __call($method, $parameters)
    {
        // Call the method that we are aliasing
        return call_user_func_array(array($this, $this->aliasedFunctions[$method]), $parameters);
    }
}

/**
 * Used as an enumeration for all of the different keys which are available
 * for data type validation.
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @package Database
 */
class DatabaseValueType
{
	/**
	 * The type code for an escaped string
	 * @var string
	 */
	public static $ESCAPED_STRING='es';
	
	/**
	 * The type code for a string
	 * @var string
	 */
	public static $STRING='s';
	
	/**
	 * The type code for an unsigned integer
	 * @var string
	 */
	public static $UNSIGNED_INTEGER='ud';
	
	/**
	 * The type code for a signed integer 
	 * @var string
	 */
	public static $SIGNED_INTEGER='d';
	
	/**
	 * The type code for an unsigned integer
	 * @var string
	 */
	public static $UNSIGNED_DECIMAL='uf';
	
	/**
	 * The type code for an signed decimal
	 * @var string
	 */
	public static $SIGNED_DECIMAL='f';
	
	/**
	 * The type code for a binary value
	 * @var string
	 */
	public static $BINARY='b';
	
	/**
	 * The type code for a list
	 * @var string
	 */
	public static $VALUE_LIST='l';
	
	/**
	 * The type code for a list of unsigned integers
	 * @var string
	 */
	public static $VALUE_LIST_UNSIGNED_INTEGER='lud';
	
	/**
	 * The type code for a list of signed integers
	 * @var string
	 */
	public static $VALUE_LIST_SIGNED_INTEGER='ld';
	
	/**
	 * The type code for a list of unsigned decimals
	 * @var string
	 */
	public static $VALUE_LIST_UNSIGNED_DECIMAL='luf';
	
	/**
	 * The type code for a list of signed decimals
	 * @var string
	 */
	public static $VALUE_LIST_SIGNED_DECIMAL='lf';
	
	/**
	 * The type code for a list of strings
	 * @var string
	 */
	public static $VALUE_LIST_STRING='ls';
	
	/**
	 * The type code for a list of escaped strings
	 * @var string
	 */
	public static $VALUE_LIST_ESCAPED_STRING='les';
	
}