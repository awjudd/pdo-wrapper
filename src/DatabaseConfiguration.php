<?php

namespace Awjudd\PDO;

use Exception;
use InvalidArgumentException;

/**
 * Used to define all of the configuration settings for the database
 * object.
 *
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
class DatabaseConfiguration
{
    /**
     * Constants defining the degree of error reporting.
     */

    /**
     * Ignore any errors.
     *
     * @var int
     */
    const ERRORS_IGNORE = 0;

    /**
     * Echo the error to the screen.
     *
     * @var int
     */
    const ERRORS_ECHO = 1;

    /**
     * Throw an exception when there is an error.
     *
     * @var int
     */
    const ERRORS_EXCEPTION = 2;

    /**
     * Write the exception to a log file.
     *
     * @var int
     */
    const ERRORS_LOGFILE = 4;

    /**
     * Default query parser.
     *
     * @var int
     */
    const QUERY_DEFAULT = 0;

    /**
     * Classic query parser.
     *
     * @var int
     */
    const QUERY_CLASSIC = 1;

    /**
     * Defines the host name for the database connection.
     *
     * @var string
     */
    public $hostname = 'localhost';

    /**
     * The database engine to connect to the database with.
     *
     * @var string
     */
    public $engine = 'mysql';

    /**
     * The name of the database which is being used.
     *
     * @var string
     */
    public $database = '';

    /**
     * The username used to log into the database server.
     *
     * @var string
     */
    public $username = '';

    /**
     * The password used to log into the database server.
     *
     * @var string
     */
    public $password = '';

    /**
     * Configuration stating how any issues which arise should be handled.
     *
     * @var int
     */
    public $errorReporting = self::ERRORS_EXCEPTION;

    /**
     * Used if the error logging is set to ERROR_LOGFILE.  This will contain the
     * file name that should be written to.
     *
     * @var array
     */
    public $errorLogFile = null;

    /**
     * Whether or not a query log should be maintained.
     *
     * @var bool
     */
    public $maintainQueryLog = true;

    /**
     * Which parser to use.
     *
     * @var int
     */
    public $queryMode = self::QUERY_DEFAULT;

    /**
     * This function is used statically in order to make an instance of a database
     * configuration object from an INI file.
     *
     * @param string $iniFile The .ini file path
     * @param string $section The section in the ini file which the database connection
     *                        information is stored.  Default: NULL, no sections
     *
     * @throws Exception::If                the file does not exist
     * @throws InvalidArgumentException::If the INI file is invalid
     *
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromINIFile($iniFile, $section = null)
    {
        // Check if the file exists
        if (file_exists($iniFile)) {
            $parseSections = $section !== null;

            // The file exists, so we'll load it from the file
            $config = parse_ini_file($iniFile, $parseSections);

            // Check if there was an error
            if ($config === false) {
                throw new InvalidArgumentException('Invalid INI file provided.');
            }

            // If there was a section provided, then move to that level
            if ($parseSections) {
                $config = $config[$section];
            }

            // Configure the object
            return self::fromArray($config);
        } else {
            // Otherwise give an error
            throw new Exception('Configuration file not available');
        }
    }

    /**
     * This function is used statically in order to make an instance of a database
     * configuration object from an INI string.
     *
     * @param string $iniString The INI string
     * @param string $section   The section in the ini file which the database connection
     *                          information is stored.  Default: NULL, no sections
     *
     * @throws Exception::If                the file does not exist
     * @throws InvalidArgumentException::If the INI string is invalid
     *
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromINIString($iniString, $section = null)
    {
        // Check if string is empty
        if (!empty($iniString)) {
            $parseSections = $section !== null;

            // The file exists, so we'll load it from the file
            $config = parse_ini_string($iniString, $parseSections);

            // Check if there was an error
            if ($config === false) {
                throw new InvalidArgumentException('Invalid INI string provided.');
            }

            // If there was a section provided, then move to that level
            if ($parseSections) {
                $config = $config[$section];
            }

            // Configure the object
            return self::fromArray($config);
        } else {
            // Otherwise give an error
            throw new Exception('Configuration string not available');
        }
    }

    /**
     * This function is used in order to configure the DatabaseConfiguration
     * object from an array.
     *
     * @param array $config The array of values to configure
     *
     * @throws InvalidArgumentException
     *
     * @return DatabaseConfiguration A fully hydrated database configuration object
     */
    public static function fromArray($config)
    {
        $obj = new self();

        // Check if the hostname is set
        if (isset($config['Hostname'])) {
            $obj->hostname = $config['Hostname'];
        }

        // Check if the database engine is set
        if (isset($config['Engine'])) {
            $obj->engine = $config['Engine'];
        }

        // Check if the database name is set
        if (isset($config['Database'])) {
            $obj->database = $config['Database'];
        }

        // Check if the username is set
        if (isset($config['Username'])) {
            $obj->username = $config['Username'];
        }

        // Check if the password is set
        if (isset($config['Password'])) {
            $obj->password = $config['Password'];
        }

        // Check if the error reporting level has been set
        if (isset($config['ErrorReporting'])) {
            $val = $config['ErrorReporting'];

            // Calculate the maximum
            $max = self::ERRORS_LOGFILE | self::ERRORS_EXCEPTION | self::ERRORS_ECHO;

            // Make sure the value is within the correct range
            if (intval($val) == $val && $val >= self::ERRORS_IGNORE && $val <= $max) {
                $obj->errorReporting = $val;
            } else {
                throw new InvalidArgumentException('Invalid value provided for configuration value "ErrorReporting".');
            }
        }

        // Check if the error file has been set
        if (isset($config['ErrorLog'])) {
            $obj->errorLogFile = $config['ErrorLog'];
        }

        // Check if the query log has been set
        if (isset($config['LogQueries'])) {
            // Check if the value is a string
            if (!is_bool($config['LogQueries'])) {
                // Check if it is either 'true' or 'false'
                $val = strtolower($config['LogQueries']);

                if ($val == 'true' || $val == 'false') {
                    // Then assign it as TRUE/FALSE
                    $config['LogQueries'] = $val === 'true';
                } else {
                    // Otherwise, invalid so throw an exception
                    throw new InvalidArgumentException('Invalid value provided for configuration value "LogQueries"');
                }
            }

            // Make sure the value is a boolean
            if (is_bool($config['LogQueries'])) {
                $obj->maintainQueryLog = $config['LogQueries'];
            }
        }

        // Check if the user wanted to log the errors
        if (($obj->errorReporting & self::ERRORS_LOGFILE) == self::ERRORS_LOGFILE) {
            // They wanted a log file, so make sure they provided a name
            if (empty($obj->errorLogFile)) {
                throw new InvalidArgumentException('Invalid Configuration.  Error file logging, but no error file provided.');
            }
        }

        // Check if the query mode has been set
        if (isset($config['QueryMode'])) {
            $val = $config['QueryMode'];

            // Calculate the maximum
            $max = self::QUERY_CLASSIC;

            // Make sure the value is within the correct range
            if (intval($val) == $val && $val >= self::QUERY_DEFAULT && $val <= $max) {
                $obj->queryMode = $val;
            } else {
                throw new InvalidArgumentException('Invalid value provided for configuration value "QueryMode".');
            }
        }

        // Return the hydrated object
        return $obj;
    }
}