<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (10/3/13)
 *
 * @copyright    La Cuarta Edad
 *
 */


namespace Vendor;

use ArrayAccess;
use stdClass;

class Item extends stdClass implements ArrayAccess
{
    public static function fromArray(array $array = array())
    {
        $object = new static;

        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return (isset($this->$offset)) ? $this->$offset : false;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        if (isset($this->$offset)) {
            unset($this->$offset);
        }
    }
}