<?php

namespace Awjudd\PDO\Tests\Configuration;

use Awjudd\PDO\Database;
use Awjudd\PDO\Tests\TestCase;
use PHPUnit_Framework_TestCase;
use Awjudd\PDO\Database\Configuration;

class ConfigurationTest extends TestCase
{
    /**
     * This test is used in order to test the configuration object's
     * setup through the use of an array.
     */
    public function testConfigureArray()
    {
        // Create an instance of the configuration object
        $config = parse_ini_file($this->getConfigurationFile());

        // Build the Configuration from an array
        $config = Configuration::fromArray($config);

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('sqlite', $config->engine);
        $this->assertEquals(':memory:', $config->database);
        $this->assertEquals(2, $config->errorReporting);
        $this->assertEquals(true, $config->maintainQueryLog);
        $this->assertEquals('testing', $config->username);
        $this->assertEquals('testing', $config->password);
        $this->assertEquals(1, $config->queryMode);

        // To change it on the older pages
        $config->queryMode = Configuration::QUERY_CLASSIC;

        // Then default it in your site to for the new parser:
        $config->queryMode = Configuration::QUERY_DEFAULT;
    }

    /**
     * This test is used in order to test the configuration object's
     * setup through the use of an INI file.
     */
    public function testConfigureINIFile()
    {
        // Build the Configuration from an INI file
        $config = Configuration::fromINIFile($this->getConfigurationFile());

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('sqlite', $config->engine);
        $this->assertEquals(':memory:', $config->database);
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
        $file = file_get_contents($this->getConfigurationFile());

        // Build the Configuration from an INI string
        $config = Configuration::fromINIString($file);

        $this->assertEquals('127.0.0.1', $config->hostname);
        $this->assertEquals('sqlite', $config->engine);
        $this->assertEquals(':memory:', $config->database);
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
        $config = parse_ini_file($this->getConfigurationFile());

        // Overwrite the error reporting level (invalid)
        $config['ErrorReporting'] = 8;

        Configuration::fromArray($config);
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
        $config = parse_ini_file($this->getConfigurationFile());

        // Overwrite the query logging boolean value
        $config['LogQueries'] = 9;

        Configuration::fromArray($config);
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
        $config = parse_ini_file($this->getConfigurationFile());
        unset($config['ErrorLog']);

        // Overwrite the query logging boolean value
        $config['ErrorReporting'] = Configuration::ERRORS_LOGFILE;
        Configuration::fromArray($config);
    }
}
