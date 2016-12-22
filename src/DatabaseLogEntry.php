<?php

namespace Awjudd\PDO;

/**
 * Base object used in the logging of database events (if logging is enabled).
 *
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
class DatabaseLogEntry
{
    /**
     * The number of milliseconds it took to execute the step.
     *
     * @var int
     */
    public $duration = 0;

    /**
     * The message for the log.
     *
     * @var string
     */
    public $message = null;

    /**
     * The query which was run.
     *
     * @var DatabaseQuery
     */
    public $query = null;

    /**
     * The entire stack trace.
     *
     * @var array
     */
    public $backtrace = null;

    /**
     * Overrides the default __toString function to give more valuable
     * information.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        // Build the string to return
        $string .= 'Query: '.$this->query.PHP_EOL
            .'Duration: '.$this->duration.' ms'.PHP_EOL;

        // Check if there is a message
        if ($this->message !== null) {
            // There is, so include it
            $string .= 'Message: '.$this->message;
        }

        return $string;
    }
}
