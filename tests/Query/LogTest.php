<?php

namespace Awjudd\PDO\Tests\Query;

use Awjudd\PDO\Database;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Tests\TestCase;
use Awjudd\PDO\Database\Configuration;

class QueryLogTest extends TestCase
{
    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = Configuration::fromINIFile($this->getConfigurationFile());
    }
    
    public function testNoQueryLog()
    {
        // Disable logging
        $this->config->maintainQueryLog = false;

        // Create an instance of the database object
        $this->db = new Database($this->config);

        // Build the required database
        $this->buildDatabase();

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(Query::class, $res);

        // Verify that there are no elements in the log
        $this->assertEquals(count($this->db->getLog()), 0);
    }

    public function testQueryLogLength()
    {
        // Create an instance of the database object
        $this->db = new Database($this->config);

        // Build the required database
        $this->buildDatabase();

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(Query::class, $res);

        // Verify that there are no elements in the log
        $this->assertEquals(count($this->db->getLog()), 6);
    }
}
