<?php

/**
 * Definition Object class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 La Cuarta Edad
 *
 */

namespace Vendor;

use ArrayAccess;
use ArrayIterator;
use Closure;
use IteratorAggregate;
use JsonSerializable;
use OutOfRangeException;
use stdClass;
use UnderflowException;

class Object implements ArrayAccess, IteratorAggregate, JsonSerializable
{

    private $_member = [];
    private $_name;
    private $_parent;

    public function __construct($name = 'root', $parent = null)
    {
        $this->_name = $name;
        $this->_parent = $parent;
    }

    public function __call($argv0, $argv)
    {
        if (isset($this->_member[$argv0])
            && $this->_member[$argv0] instanceof Closure
        ) {
            return call_user_func_array($this->_member[$argv0], $argv);
        }

        call_user_func_array(
            array($this, '__set'),
            array_merge(array($argv0), $argv)
        );

        return $this;
    }

    public function __isset($name)
    {
        return isset($this->_member[$name])
        && (!($this->_member[$name] instanceof self));
    }

    public function __get($name)
    {
        if (!isset($this->_member[$name])) {
            return false;
        }

        return $this->_member[$name];
    }

    public function __set($name, $value = null)
    {
        $argv = array_slice(func_get_args(), 1);
        array_walk_recursive(
            $argv,
            function (&$value) {
                if ($value instanceof Closure) {
                    $value = $value->bindTo($this);
                }
            }
        );

        $this->_member[$name] = (count($argv) > 1)
            ? $argv
            : (isset($argv[0]) ? $argv[0] : null);
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    public function __unset($name)
    {
        if (isset($this->_member[$name])) {
            unset($this->_member[$name]);
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_member);
    }

    public function jsonSerialize()
    {
        $json = new stdClass();

        foreach ($this->_member as $key => $value) {
            if ($value instanceof Closure) {
                $value = $value();
            }

            $json->$key = $value;
        }

        return $json;
    }

    public function offsetExists($offset)
    {
        return isset($this->_member[$offset])
        && ($this->_member[$offset] instanceof self);
    }

    public function offsetGet($offset)
    {
        if ($offset == "/{$this->_name}") {
            return (isset($this->_parent)) ? $this->_parent : $this;
        }

        if (!array_key_exists($offset, $this->_member)) {
            $this->__set($offset, new self($offset, $this));
        }

        return $this->_member[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new UnderflowException(
            "Underflow: Impossible to set offset '{$offset}'"
        );
    }

    public function offsetUnset($offset)
    {
        if (isset($this->_member[$offset])
            && ($this->_member[$offset] instanceof self)
        ) {
            unset($this->_member[$offset]);
        } elseif (isset($this->_member[$offset])) {
            throw new OutOfRangeException("Out of Range: '{$offset}'");
        }
    }
}
