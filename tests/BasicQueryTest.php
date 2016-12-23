<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Database\Configuration;

class BasicQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * The configuration object to be used everywhere.
     *
     * @var Configuration
     */
    private $config = null;

    /**
     * The database object to use.
     *
     * @var Database
     */
    private $db = null;

    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = Configuration::fromINIFile(__DIR__ . '/testconfig.ini');
        $this->config->queryMode = Configuration::QUERY_DEFAULT;

        // Create an instance of the database object
        $this->db = new Database($this->config);
    }

    public function testQuery()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE bar={0:ud}', 0);

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type Query
        $this->assertInstanceOf(Query::class, $res);
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
        $this->db->query('SELECT * FROM foo WHERE bar={0:ud}');
    }

    public function testQueryInsufficientLocationParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('OutOfBoundsException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE bar={1:ud} AND blah={0:s}', 'hi');
    }

    public function testQueryInferredAndLocationParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE bar={0:ud} AND blah={s}', 1);
    }

    public function testQueryInvalidDataTypeParameters()
    {
        // We are expecting an exception
        $this->setExpectedException('InvalidArgumentException');

        // Query the database
        $this->db->query('SELECT * FROM foo WHERE blah={0:ud}', 'hi');
    }

    public function testQueryListTypeParameters()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN {0:ld} AND bar IN {1:lud}', '0,-1,2', array(0, 1, 2));

        // Make sure that something is returned
        $this->assertNotNull($res);

        // And that it is of type Query
        $this->assertInstanceOf(Query::class, $res);
    }

    public function testQueryString()
    {
        $res = $this->db->query('SELECT bar FROM foo WHERE bar = {0:s}', 'hello');

        if ($res->numberOfRows == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function testQueryResults()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo ORDER BY bar ASC');

        // Make sure that something is returned
        $this->assertNotNull($res);
        $this->assertEquals($res['foo'], 'asdf');
        $this->assertEquals($res[2]['foo'], 'Testing');

        // And that it is of type Query
        $this->assertInstanceOf(Query::class, $res);
    }
}
