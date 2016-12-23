<?php

namespace Awjudd\PDO\Database;

/**
 * Used as an enumeration for all of the different keys which are available
 * for data type validation.
 *
 * @author Andrew Judd <contact@andrewjudd.ca>
 * @copyright Andrew Judd, 2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
class ValueType
{
    /**
     * The type code for an escaped string.
     *
     * @var string
     */
    public static $ESCAPED_STRING = 'es';

    /**
     * The type code for a string.
     *
     * @var string
     */
    public static $STRING = 's';

    /**
     * The type code for an unsigned integer.
     *
     * @var string
     */
    public static $UNSIGNED_INTEGER = 'ud';

    /**
     * The type code for a signed integer.
     *
     * @var string
     */
    public static $SIGNED_INTEGER = 'd';

    /**
     * The type code for an unsigned integer.
     *
     * @var string
     */
    public static $UNSIGNED_DECIMAL = 'uf';

    /**
     * The type code for an signed decimal.
     *
     * @var string
     */
    public static $SIGNED_DECIMAL = 'f';

    /**
     * The type code for a binary value.
     *
     * @var string
     */
    public static $BINARY = 'b';

    /**
     * The type code for a list.
     *
     * @var string
     */
    public static $VALUE_LIST = 'l';

    /**
     * The type code for a list of unsigned integers.
     *
     * @var string
     */
    public static $VALUE_LIST_UNSIGNED_INTEGER = 'lud';

    /**
     * The type code for a list of signed integers.
     *
     * @var string
     */
    public static $VALUE_LIST_SIGNED_INTEGER = 'ld';

    /**
     * The type code for a list of unsigned decimals.
     *
     * @var string
     */
    public static $VALUE_LIST_UNSIGNED_DECIMAL = 'luf';

    /**
     * The type code for a list of signed decimals.
     *
     * @var string
     */
    public static $VALUE_LIST_SIGNED_DECIMAL = 'lf';

    /**
     * The type code for a list of strings.
     *
     * @var string
     */
    public static $VALUE_LIST_STRING = 'ls';

    /**
     * The type code for a list of escaped strings.
     *
     * @var string
     */
    public static $VALUE_LIST_ESCAPED_STRING = 'les';
}
