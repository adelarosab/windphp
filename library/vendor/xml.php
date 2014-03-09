<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      0.1
 *
 * @version      1.0 (05/12/2013)
 *               Bugfix isset. Array access implemented.
 *               Performance improvements.
 * @version      1.1 (05/12/2013)
 *               Array Acces use to access attribute's elements.
 * @version      1.2 (05/12/2013)
 *               toString implemented.
 *
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor;

use ArrayAccess;
use ErrorException;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

class XML implements ArrayAccess
{

    private $root;

    public function __construct(SimpleXMLElement $sxmle)
    {
        $this->root = $sxmle;
    }

    public function __get($name)
    {
        if (
            isset($this->root->$name)
            && $this->root->$name
                ->children()
                ->count()
        ) {
            $element = array();
            foreach ($this->root->$name as $value) {
                $element[] = new self($value);
            }

            return (count($element) > 1) ? $element : $element[0];
        } elseif (isset($this->root->$name)) {
            return (string)$this->root->$name;
        } else {
            return null;
        }
    }

    public function __isset($name)
    {
        return $this->root->$name !== null;
    }

    public function __toString()
    {
        return $this->root->asXML();
    }

    public static function fromString($string)
    {
        try {
            set_error_handler(
                function () {
                },
                E_WARNING
            );
            $xml = new SimpleXMLElement($string);
            restore_error_handler();

            return new self($xml);
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "XML: Malformed XML. {$e->getMessage()}"
            );
        }
    }

    public static function fromFile($file)
    {
        if (!file_exists($file)) {
            throw new ErrorException("File: File not found {$file}");
        }

        $string = file_get_contents($file);
        if ($string === false) {
            throw new ErrorException(
                "File: Impossible to read {$file}. Check permissions"
            );
        }

        return self::fromString($string);
    }

    public function offsetExists($offset)
    {
        return isset($this->root->attributes()[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->root->attributes()[$offset];
    }

    public function offsetSet($offset, $value)
    {
        return;
    }

    public function offsetUnset($offset)
    {
        return;
    }
}