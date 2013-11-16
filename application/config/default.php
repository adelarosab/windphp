<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (10/10/13)
 *
 * @copyright    La Cuarta Edad
 *
 */


$CFG = (new Object)
    ->develop(true)
    ->seed('ab86f03a9ba1d5d83266de0a6a2517aa');

if (isset($CFG->develop) && $CFG->develop) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

function debug($flag = DEBUG_ALL)
{
    header('Content-Type: text/plain');

    API::$DEBUG = $flag;
    DataSource::$DEBUG = $flag;
    OAuth::$DEBUG = $flag;
    URL::$DEBUG = $flag;
}