<?php

/**
 * Definition model class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use Exception;
use Vendor\Object;

class Definition extends Object
{

    const PRIMARY_KEY = 'ID';
    const SEPARATOR = '';

    const BINARY = 1;
    const BLOB = 1;
    const BOOL = 2;
    const DATE = 4;
    const DATETIME = 8;
    const DOUBLE = 16;
    const FLOAT = 32;
    const INT = 64;
    const REFERENCE = 128;
    const TEXT = 256;
    const TIME = 512;
    const TIMESTAMP = 1024;
    const VARCHAR = 256;

    public static $list = [];

    private $_name;
    private $_isNormalized = false;

    public function __construct($name)
    {
        parent::__construct();

        $this->_name = $name;

        $this[self::PRIMARY_KEY]
            ->type(self::INT);
    }

    public function __call($argv0, $argv)
    {
        try {
            $closure = $this->_definition->$argv0;

            return call_user_func_array($closure, $argv);
        }
        catch (Exception $e) {
            // noop
        }

        return call_user_func_array("parent::{$argv0}", $argv);
    }

    private function normalize()
    {
        if (!$this->_isNormalized) {
            $this->_isNormalized = true;
        } else {
            return null;
        }

        // general properties of model
        if (!isset($this->key)) {
            $this->key = self::PRIMARY_KEY;
        }

        if (!isset($this->table)) {
            $this->table = strtolower($this->_name);
        }

        // attributes
        foreach ($this as $key => &$value) {
            if (!($value instanceof Object)) {
                continue;
            }

            // type
            if (!isset($value->type)) {
                $value->type = self::INT;
            }

            $type = ((is_array($value->type)) ? $value->type[0] : $value->type);
            if (!is_int($type)) {
                $type = (constant("self::{$type}"))
                    ? constant("self::{$type}")
                    : self::INT;

                if ($type & self::REFERENCE) {
                    $value->reference = $value->type[1];
                }

                $value->type = $type;
            }

            // default values to references
            if ($type & self::REFERENCE) {
                $reference = $value->reference;

                if (!isset($reference)) {
                    continue;
                }

                if (!isset($value->key)) {
                    if (isset($value->belongsTo)) {
                        $value->key = strtolower($key) . self::PRIMARY_KEY;
                    } else {
                        $value->key
                            = strtolower($this->_name) . self::PRIMARY_KEY;
                    }
                }

                if (!isset($value->table)
                    && isset(Definition::$list[$reference])
                ) {
                    if (isset($value->belongsTo)) {
                        $value->table = $this->table;
                    } else {
                        $model = Definition::$list[$reference];

                        if (!isset($model->table)) {
                            $model->normalize();
                        }

                        $value->table = $model->table;
                    }
                }
            }
        }
    }

    public function dependencies()
    {
        $this->normalize();

        $dependencies = [];

        $fields = array_filter(
            $this,
            function ($value) {
                return ($value instanceof Object)
                && ($value->type & self::REFERENCE);
            }
        );

        foreach ($fields as $key => $value) {
            $dependencies[$key] = $value->reference;
        }

        return $dependencies;
    }

    public function validate(&$data)
    {
        $errors = [];
        $this->normalize();

        foreach ($this as $key => $value) {
            if (!($value instanceof Object)) {
                continue;
            }

            $validation = (isset($value['validation']))
                ? $value['validation']
                : null;

            if (!isset($data[$key])) {
                if (isset($validation)
                    && isset($validation->required)
                    && $validation->required
                ) {
                    return $validation->required;
                }
                continue;
            }

            if ($value->type & self::REFERENCE) {
                foreach ($data[$key] as $rvalue) {
                    if ($validation = $rvalue->validate()) {
                        return $validation;
                    }
                }

                continue;
            }

            $rvalue = $value[$key];
            switch ($value->type) {
            case self::BINARY:
            case self::BLOB:
                break;

            case self::BOOL:
                $rvalue = (int) ((bool) $rvalue);
                break;

            case self::DATE:
                if (!is_int($rvalue)) {
                    $rvalue = strtotime($rvalue);
                }
                $rvalue = date('Y-m-d', $rvalue);
                break;

            case self::DATETIME:
            case self::TIMESTAMP:
                if (!is_int($rvalue)) {
                    $rvalue = strtotime($rvalue);
                }
                $rvalue = date('Y-m-d H:i:s', $rvalue);
                break;

            case self::FLOAT:
                $rvalue = (float) $rvalue;
                break;

            case self::TIME:
                if (!is_int($rvalue)) {
                    $rvalue = strtotime($rvalue);
                }
                continue;

            case self::TEXT:
            case self::VARCHAR:
                $rvalue = (string) $rvalue;
                break;

            case self::INT:
            default:
                $rvalue = (int) $rvalue;
                break;
            }
            $data[$key] = $rvalue;

            if (!isset($validation)) {
                continue;
            }

            foreach ($validation as $rkey => $rvalue) {
                switch ($rkey) {
                case 'alphanumeric':
                    $error = !preg_match('/\w+/', $data[$key]);
                    break;

                case 'blank':
                    $error = !empty($data[$key]);
                    break;

                case 'decimal':
                    $error = !preg_match('/(\+-)?\d+(\.\d+)?/', $data[$key]);
                    break;

                case 'email':
                    $error = !preg_match(
                        '/[\w\d.]+@[\w\d.]+\.[\w]+',
                        $data[$key]
                    );
                    break;

//                case 'extension':
//                    break;

//                case 'ip':
//                    break;

                case 'length':
                    $min = (is_array($rvalue) && isset($rvalue[0]))
                        ? $rvalue[0]
                        : $rvalue;
                    $max = (is_array($rvalue)
                        && isset($rvalue[1])
                        && is_int($rvalue[1]))
                        ? $rvalue[1]
                        : PHP_INT_MAX;
                    $length = strlen($data[$key]);

                    $error = $length < $min || $length > $max;
                    $rvalue = (is_array($rvalue)
                        && isset($rvalue[1])
                        && !is_int($rvalue[1]))
                        ? $rvalue[1]
                        : ((is_array($rvalue) && isset($rvalue[2]))
                            ? $rvalue[2]
                            : '');
                    break;

                case 'numeric':
                    $error = !preg_match('/(\+-)?\d+/', $data[$key]);
                    break;

//                case 'phone':
//                    break;

//                case 'url':
//                    break;

                default:
                    $error = !preg_match($rkey, $data[$key]);
                    break;
                }

                if ($error) {
                    $errors[$key][$rkey] = $rvalue;
                }
            }
        }

        return $errors;
    }
}