<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.0 (11/03/2012)
 * @version     1.1 (05/28/2013)
 *              Ordered array of arguments to fetch.
 *
 * This class abstracts url requests as an API, using PROTECTED methods for
 * generating request's information.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor;

use BadMethodCallException;

abstract class API
{

    public static $DEBUG = DEBUG_NONE;

    protected $connector;

    public function __construct()
    {
        $this->connector = new URL();
    }

    final public function __call($argv0, $argv)
    {
        if (!method_exists($this, $argv0)) {
            throw new BadMethodCallException($argv0);
        }

        $default = array(
            'url' => null,
            'method' => URL::GET,
            'data' => array(),
            'options' => array()
        );

        $argvDebug = $argv;

        $argv = array_merge(
            $default,
            call_user_func_array(array($this, $argv0), $argv)
        );

        if (self::$DEBUG & DEBUG_API) {
            $argvDebug = array_map(
                function ($value) {
                    return (strlen($value) > 512) ? 'file' : $value;
                },
                $argvDebug
            );

            URL::$DEBUG = self::$DEBUG;
            print str_pad('', 75, '=') . PHP_EOL;
            print 'API' . PHP_EOL;
            print str_pad('', 75, '=') . PHP_EOL;
            print "{$argv0}(" . implode(', ', $argvDebug) . ')' . PHP_EOL;
            print str_pad('', 75, '*') . PHP_EOL;
        }

        return call_user_func_array(
            array($this->connector, 'fetch'),
            $argv
        );
    }
}
