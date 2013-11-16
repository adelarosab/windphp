<?php

/**
 *
 * @author      Adrián de la Rosa
 * @version     1.0 (05/08/2012)
 * @version     2.0 (10/06/2012)
 * @version     2.1 (02/14/2013)
 *              Method append improved, now is implemented checking types.
 *
 * @version     3.0 (04/26/2013)
 *              ArrayAcces implemented. Method set removed by __set.
 *              Added __isset, __unset methods.
 *
 * @version     4.0 (06/14/2013)
 *              Implemented a smart method which can return parent.
 *              Remove getInstance method.
 * @version     4.1 (06/15/2013)
 *              NotAllowed names array added.
 *              From now on non-accessible names are saved in this array.
 *
 * @version     5.0 (06/20/2013)
 *              Reimplemented.
 *              From now on class use interface JsonSerializable,
 *              it does the class dont have to extends stdClass.
 *              Added IteratorAggregate for simulate foreach behaviour.
 *
 * @copyright   La Cuarta Edad
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

    private $name;
    private $parent;

    protected $member = array();

    public function __construct($name = 'root', $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
    }

    public function __call($argv0, $argv)
    {
        if (isset($this->member[$argv0])
            && $this->member[$argv0] instanceof Closure
        ) {
            return call_user_func_array($this->member[$argv0], $argv);
        }

        call_user_func_array(
            array($this, '__set'),
            array_merge(array($argv0), $argv)
        );

        return $this;
    }

    public function __isset($name)
    {
        return isset($this->member[$name])
        && (!($this->member[$name] instanceof self));
    }

    public function __get($name)
    {
        if (!isset($this->member[$name])
            || $this->member[$name] instanceof Closure
        ) {
            throw new OutOfRangeException("Out of Range: '{$name}'");
        }

        return $this->member[$name];
    }

    public function __set($name, $value = null)
    {
        $argv = array_slice(func_get_args(), 1);
        array_walk_recursive(
            $argv,
            function (&$value) {
                if ($value instanceof Closure) {
                    $value = $value->{'bindTo'}($this);
                }
            }
        );

        $this->member[$name] = (count($argv) > 1)
            ? $argv
            : (isset($argv[0]) ? $argv[0] : null);
    }

    public function __toString()
    {
        return $this->name;
    }

    public function __unset($name)
    {
        if (isset($this->member[$name])
            && !($this->member[$name] instanceof self)
        ) {
            unset($this->member[$name]);
        } elseif (isset($this->member[$name])) {
            throw new OutOfRangeException("Out of Range: '{$name}'");
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->member);
    }

    public function jsonSerialize()
    {
        $json = new stdClass();

        foreach ($this->member as $key => $value) {
            if ($value instanceof Closure) {
                $value = $value();
            }

            $json->$key = $value;
        }

        return $json;
    }

    public function offsetExists($offset)
    {
        return isset($this->member[$offset])
        && ($this->member[$offset] instanceof self);
    }

    public function offsetGet($offset)
    {
        if ($offset == "/{$this->name}") {
            if (isset($this->parent)) {
                return $this->parent;
            }

            return $this;
        }

        if (!array_key_exists($offset, $this->member)) {
            $this->__set($offset, new self($offset, $this));
        }

        if (!($this->member[$offset] instanceof self)) {
            throw new OutOfRangeException("Out of Range: '{$offset}'");
        }

        return $this->member[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new UnderflowException(
            "Underflow: Impossible to set offset '{$offset}'"
        );
    }

    public function offsetUnset($offset)
    {
        if (isset($this->member[$offset])
            && ($this->member[$offset] instanceof self)
        ) {
            unset($this->member[$offset]);
        } elseif (isset($this->member[$offset])) {
            throw new OutOfRangeException("Out of Range: '{$offset}'");
        }
    }
}
