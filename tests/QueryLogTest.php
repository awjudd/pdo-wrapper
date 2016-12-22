<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\DatabaseQuery;
use Awjudd\PDO\DatabaseConfiguration;

class QueryLogTest extends PHPUnit_Framework_TestCase
{
    private $config = null;
    private $db = null;

    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = DatabaseConfiguration::fromINIFile(__DIR__ . '/testconfig.ini');
        $this->config->queryMode = DatabaseConfiguration::QUERY_CLASSIC;
    }

    public function testNoQueryLog()
    {
        // Disable logging
        $this->config->maintainQueryLog = false;

        // Create an instance of the database object
        $this->db = new Database($this->config);

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(DatabaseQuery::class, $res);

        // Verify that there are no elements in the log
        $this->assertEquals(count($this->db->getLog()), 0);
    }

    public function testQueryLogLength()
    {
        // Create an instance of the database object
        $this->db = new Database($this->config);

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(DatabaseQuery::class, $res);

        // Verify that there are no elements in the log
        $this->assertEquals(count($this->db->getLog()), 2);
    }
}
