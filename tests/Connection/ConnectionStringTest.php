<?php

namespace Awjudd\PDO\Tests\Connection;

use Awjudd\PDO\Tests\TestCase;
use Awjudd\PDO\Database\Configuration;

class ConnectionStringTest extends TestCase
{
    public function testMysqlConnection()
    {
        $config = Configuration::fromArray([
            'Engine' => 'mysql',
            'Hostname' => 'foo',
            'Database' => 'bar',
        ]);

        $this->assertEquals('mysql:host=foo;dbname=bar', $config->getConnectionString());
    }
}