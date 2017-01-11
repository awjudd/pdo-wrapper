<?php

namespace Awjudd\PDO\Tests\Query;

use Awjudd\PDO\Database;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Tests\TestCase;
use Awjudd\PDO\Database\Configuration;

class ClassicTest extends TestCase
{
    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = Configuration::fromINIFile($this->getConfigurationFile());

        // Create an instance of the database object
        $this->db = new Database($this->config);

        // Build the required database
        $this->buildDatabase();
    }
    
    public function testQuery()
    {
        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE bar=%ud', 0);

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

        // And that it is of type Query
        $this->assertInstanceOf(Query::class, $res);
    }
}
