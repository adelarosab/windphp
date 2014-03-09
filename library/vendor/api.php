<?php

/**
 *
 * This class abstracts url requests as an API, using PROTECTED methods for
 * generating request's information.
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 A Tale Company
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

    protected function debug($message)
    {
        if (self::$DEBUG & DEBUG_API) {
            echo str_pad('', 75, '-') . PHP_EOL;
            echo 'API' . PHP_EOL;
            echo str_pad('', 75, '=') . PHP_EOL;
            echo $message . PHP_EOL;
            echo str_pad('', 75, '*') . PHP_EOL;
        }

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

        $this->debug("{$argv0}(" . implode(', ', $argv) . ')');

        $argv = array_merge(
            $default,
            call_user_func_array(array($this, $argv0), $argv)
        );

        return call_user_func_array(
            array($this->connector, 'fetch'),
            $argv
        );
    }
}
