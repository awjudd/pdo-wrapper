<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\DatabaseConfiguration;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    private $configFile = __DIR__ . '/testconfig.ini';

    /**
     * This test is used in order to test the configuration object's
     * setup through the use of an array.
     */
    public function testConfigureArray()
    {
        // Create an instance of the configuration object
        $config = parse_ini_file($this->configFile);

        // Build the Configuration from an array
        $config = DatabaseConfiguration::fromArray($config);

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('mysql', $config->engine);
        $this->assertEquals('testing', $config->database);
        $this->assertEquals(2, $config->errorReporting);
        $this->assertEquals(true, $config->maintainQueryLog);
        $this->assertEquals('testing', $config->username);
        $this->assertEquals('testing', $config->password);
        $this->assertEquals(1, $config->queryMode);

        // To change it on the older pages
        $config->queryMode = DatabaseConfiguration::QUERY_CLASSIC;

        // Then default it in your site to for the new parser:
        $config->queryMode = DatabaseConfiguration::QUERY_DEFAULT;
    }

    /**
     * This test is used in order to test the configuration object's
     * setup through the use of an INI file.
     */
    public function testConfigureINIFile()
    {
        // Build the Configuration from an INI file
        $config = DatabaseConfiguration::fromINIFile($this->configFile);

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('mysql', $config->engine);
        $this->assertEquals('testing', $config->database);
        $this->assertEquals(2, $config->errorReporting);
        $this->assertEquals(true, $config->maintainQueryLog);
        $this->assertEquals('testing', $config->username);
        $this->assertEquals('testing', $config->password);
    }

    /**
     * This test is used in order to test the configuration object's
     * setup through the use of an INI string.
     */
    public function testConfigureINIString()
    {
        // Load the INI file into memory
        $file = file_get_contents($this->configFile);

        // Build the Configuration from an INI string
        $config = DatabaseConfiguration::fromINIString($file);

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('mysql', $config->engine);
        $this->assertEquals('testing', $config->database);
        $this->assertEquals(2, $config->errorReporting);
        $this->assertEquals(true, $config->maintainQueryLog);
        $this->assertEquals('testing', $config->username);
        $this->assertEquals('testing', $config->password);
    }

    /**
     * This test is used in order to test the validation of the error reporting
     * configuration value.
     */
    public function testInvalidErrorReportingLevel()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Load the INI file into memory
        $config = parse_ini_file($this->configFile);

        // Overwrite the error reporting level (invalid)
        $config['ErrorReporting'] = 8;

        DatabaseConfiguration::fromArray($config);
    }

    /**
     * This test is used in order to test the validation of the query logging
     * configuration value.
     */
    public function testInvalidLogQueriesLevel()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Load the INI file into memory
        $config = parse_ini_file($this->configFile);

        // Overwrite the query logging boolean value
        $config['LogQueries'] = 9;

        DatabaseConfiguration::fromArray($config);
    }

    /**
     * This test is used in order to test the validation of the query logging
     * configuration value.
     */
    public function testNoErrorLogProvided()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Load the INI file into memory
        $config = parse_ini_file($this->configFile);
        unset($config['ErrorLog']);

        // Overwrite the query logging boolean value
        $config['ErrorReporting'] = DatabaseConfiguration::ERRORS_LOGFILE;
        DatabaseConfiguration::fromArray($config);
    }
}
