<?php

/**
 *
 * @author     Adrián
 * @version    1.0 (10/28/2012)
 *
 * @copyright  La Cuarta Edad
 *
 */

namespace Vendor;

function array_column($array, $column, $key = null)
{
    $values = array_reduce(
        $array,
        function ($result, $item) use ($column) {
            $item = (array)$item;

            $result[] = $item[$column];

            return $result;
        },
        []
    );

    if (isset($key)) {
        $keys = array_reduce(
            $array,
            function ($result, $item) use ($key) {
                $result[] = $item[$key];

                return $result;
            },
            array()
        );

        $values = array_combine($keys, $values);
    }

    return $values;
}

function &array_peek(&$array)
{
    $keys = array_keys($array);
    $lastKey = $keys[count($keys) - 1];

    return $array[$lastKey];
}

function &array_poll(&$array)
{
    $keys = array_keys($array);
    $firstKey = $keys[0];

    return $array[$firstKey];
}

function array_trend($array)
{
    if (count($array) == 0) {
        return false;
    }

    $trend = array_count_values($array);
    arsort($trend);
    $trend = array_keys($trend);

    return array_shift($trend);
}

function is_capital($string)
{
    return $string == ucfirst($string);
}

function is_lowercase($string)
{
    return $string == strtolower($string);
}

function is_uppercase($string)
{
    return $string == strtoupper($string);
}

function language($key)
{
    global $LANG;

    $argv = func_get_args();

    $language = (isset($LANG[strtolower($key)]))
        ? $LANG[strtolower($key)]
        : '';

    switch (true) {
        case is_capital($key):
            $language = ucfirst($language);
            break;

        case is_uppercase($key):
            $language = strtoupper($key);
            break;

        default:
            break;
    }

    $argv[0] = $language;

    return (count($argv) > 1)
        ? call_user_func_array('sprintf', $argv)
        : $language;
}

function swap(&$a, &$b)
{
    $aux = $a;
    $a = $b;
    $b = $aux;
}
