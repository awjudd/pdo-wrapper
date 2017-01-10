<?php

namespace Awjudd\PDO\Tests;

use Awjudd\PDO\Database;
use PHPUnit_Framework_TestCase;
use Awjudd\PDO\Database\Configuration;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * The configuration object to be used everywhere.
     *
     * @var Configuration
     */
    protected $config = null;

    /**
     * The database object to use.
     *
     * @var Database
     */
    protected $db = null;

    protected function getConfigurationFile()
    {
        return __DIR__ . '/testconfig.ini';
    }
}