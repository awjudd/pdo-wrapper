<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\DatabaseQuery;
use Awjudd\PDO\DatabaseConfiguration;

class ClassicQueryTest extends PHPUnit_Framework_TestCase
{
    private $config = null;
    private $db = null;

    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = DatabaseConfiguration::fromINIFile(__DIR__ . '/testconfig.ini');
        $this->config->queryMode = DatabaseConfiguration::QUERY_CLASSIC;

        // Create an instance of the database object
        $this->db = new Database($this->config);
    }

    public function testQuery()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE bar=%ud', 0);

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(DatabaseQuery::class, $res);
    }

    public function testQueryNoParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Call the query function without any parameters
        $this->db->query();
    }

    public function testQueryInsufficientParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('OutOfBoundsException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE bar=%ud');
    }

    public function testQueryInsufficientLocationParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('OutOfBoundsException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE bar=%ud AND blah=%s');
    }

    public function testQueryInferredAndLocationParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('OutOfBoundsException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE bar=%ud AND blah=%s', 1);
    }

    public function testQueryInvalidDataTypeParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE blah=%ud', 'hi');
    }

    public function testQueryListTypeParameters()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type DatabaseQuery
        $this->assertInstanceOf(DatabaseQuery::class, $res);
    }
}
