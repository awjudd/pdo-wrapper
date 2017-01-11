<?php

namespace Awjudd\PDO\Tests\Connection;

use Awjudd\PDO\Database;
use Awjudd\PDO\Tests\TestCase;
use Awjudd\PDO\Database\Configuration;

class ConnectionTest extends TestCase
{
    public function testMySQLConnect()
    {
        // Create an instance of the configuration object
        $config = Configuration::fromINIFile($this->getConfigurationFile());

        // Create an instance of the database object
        $db = new Database($config);
    }

    public function testMySQLConnectFailed()
    {
        // We are expecting an exception
        $this->setExpectedException('PDOException');

        // Create an instance of the configuration object
        $config = Configuration::fromINIFile($this->getConfigurationFile());

        $config->engine = 'mysql';

        // Change the password
        $config->password = 'roar';

        // Disable logging except for the exception
        $config->errorReporting = Configuration::ERRORS_EXCEPTION;

        // Create an instance of the database object
        $db = new Database($config);
    }
}
