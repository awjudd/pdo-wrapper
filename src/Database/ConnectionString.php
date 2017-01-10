<?php

namespace Awjudd\PDO\Database;

use Awjudd\PDO\Database\Configuration;

class ConnectionString
{

    /**
     * Derives the connection string based on the given configuration.
     *
     * @param      \Awjudd\PDO\Database\Configuration  $configuration  The configuration
     *
     * @return     string                              The connection string 
     */
    public static function derive(Configuration $configuration)
    {
        switch($configuration->engine)
        {
            case 'mysql':
                return static::mysql($configuration);
        }
    }

    /**
     * Derives the connection string for a Mysql connection
     *
     * @param      \Awjudd\PDO\Database\Configuration  $configuration  The configuration
     *
     * @return     string                               The mysql specific connection string
     */
    public static function mysql(Configuration $configuration)
    {
        return sprintf(
            'mysql:host=%s;dbname=%s',
            $configuration->hostname,
            $configuration->database
        );
    }
}