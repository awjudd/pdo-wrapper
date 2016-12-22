<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\DatabaseConfiguration;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testMySQLConnect()
    {
        // Create an instance of the configuration object
        $config = DatabaseConfiguration::fromINIFile(__DIR__ . '/testconfig.ini');

        // Create an instance of the database object
        $db = new Database($config);
    }

    public function testMySQLConnectFailed()
    {
        // We are expecting an exception
        $this->setExpectedException('PDOException');

        // Create an instance of the configuration object
        $config = DatabaseConfiguration::fromINIFile(__DIR__ . '/testconfig.ini');

        // Change the password
        $config->password = 'roar';

        // Disable logging except for the exception
        $config->errorReporting = DatabaseConfiguration::ERRORS_EXCEPTION;

        // Create an instance of the database object
        $db = new Database($config);
    }
}
