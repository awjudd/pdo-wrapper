<?php

namespace Awjudd\PDO\Database;

use Awjudd\PDO\Database\Configuration;

class ConnectionString
{

    public static function derive(Configuration $configuration)
    {
        switch($configuration->engine)
        {
            case 'mysql':
                return static::mysql($configuration);
                break;
        }
    }

    public static function mysql(Configuration $configuration)
    {
        return sprintf(
            'mysql:host=%s;dbname=%s',
            $configuration->hostname,
            $configuration->database
        );
    }
}