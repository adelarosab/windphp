<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (07/06/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC\util;

class Singleton
{

    private static $instance = null;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new static;
        }

        return self::$instance;
    }

}