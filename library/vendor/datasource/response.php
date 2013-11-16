<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.0
 * @version     1.1 (04/26/2013)
 *              Now data it can be accessed as object.
 *
 * @version     2.0 (04/27/2013)
 *              Now that object it can be accessed as array and object.
 *              Data is private.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\DataSource;

use ArrayAccess;
use ArrayIterator;
use Vendor\Item;
use IteratorAggregate;

class Response implements ArrayAccess, IteratorAggregate
{

    private $data;

    public $affectedRows;

    public function __construct(array $data)
    {
        $this->data = $data;
        array_walk(
            $this->data,
            function (&$value) {
                $value = Item::fromArray($value);
            }
        );

        $this->affectedRows = count($data);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        if (is_int($offset)) {
            return isset($this->data[$offset]);
        } else {
            return isset($this->data[0]) && isset($this->data[0]->$offset);
        }
    }

    public function offsetGet($offset)
    {
        if (is_int($offset)) {
            return $this->data[$offset];
        } else {
            return (isset($this->data[0])) ? $this->data[0]->$offset : false;
        }
    }

    public function offsetSet($offset, $value)
    {
        return null;
    }

    public function offsetUnset($offset)
    {
        return null;
    }

    public function size()
    {
        return $this->affectedRows;
    }
}
