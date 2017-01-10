<?php

namespace Awjudd\PDO\Tests\Query;

use Awjudd\PDO\Database;
use Awjudd\PDO\Database\Query;
use Awjudd\PDO\Tests\TestCase;
use Awjudd\PDO\Database\Configuration;

class HookTest extends TestCase
{
    public function setUp()
    {
        // Create an instance of the configuration object
        $this->config = Configuration::fromINIFile($this->getConfigurationFile());

        // Create an instance of the database object
        $this->db = new Database($this->config);
    }
    
    public function testBeforeHookIsCalled()
    {
        $methodCalled = false;

        $hook = function(Query $query) use(&$methodCalled) {
            $methodCalled = true;
        };

        // Create an instance of the database object
        $db = new Database($this->config);
        $db->setBeforeHook($hook);

        // Query the database
        $res = $db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

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
        $db = new Database($this->config);
        $db->setAfterHook($hook);

        // Query the database
        $res = $db->query('SELECT * FROM foo WHERE blah IN %ld AND bar IN %lud', '0,-1,2', array(0, 1, 2));

        // Make sure the method was called
        $this->assertTrue($methodCalled);
    }
}