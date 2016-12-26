<?php

use Awjudd\PDO\Database;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Database\Configuration;

class QueryHookTest extends PHPUnit_Framework_TestCase
{
    private $config = null;
    private $db = null;

    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = Configuration::fromINIFile(__DIR__ . '/testconfig.ini');
        $this->config->queryMode = Configuration::QUERY_CLASSIC;
    }

    public function testBeforeHookIsCalled()
    {
        $methodCalled = false;

        $hook = function(Query $query) use(&$methodCalled) {
            $methodCalled = true;
        };

        // Create an instance of the database object
        $this->db = new Database($this->config);
        $this->db->setBeforeHook($hook);

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure the method was called
        $this->assertTrue($methodCalled);
    }

    public function testAfterHookIsCalled()
    {
        $methodCalled = false;

        $hook = function(Query $query) use(&$methodCalled) {
            $methodCalled = true;
        };

        // Create an instance of the database object
        $this->db = new Database($this->config);
        $this->db->setAfterHook($hook);

        // Query the database
        $res = $this->db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure the method was called
        $this->assertTrue($methodCalled);
    }
}