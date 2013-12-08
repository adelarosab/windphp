<?php

/**
 * Definition model class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use ArrayIterator;
use Closure;
use ErrorException;
use Exception;
use Vendor\Object;

class Model
{

    const PATH = APP_MODEL;

    public static $dataSource = null;

    private $_definition;
    private $_extra = [];
    private $_values = [];

    public function __construct($definition)
    {
        $this->_definition = $definition;
    }

    public function __call($argv0, $argv)
    {
        switch ($argv0) {
        case 'delete':
        case 'insert':
        case 'select':
        case 'update':
            $table = (isset($argv[0])) ? $argv[0] : $this->_definition->table;
            $query = self::$dataSource->{$argv0}($table);
            break;

        case 'query':
            $query = call_user_func_array(
                [self::$dataSource, 'statement'],
                $argv
            );
            break;

        default:
            return call_user_func_array(
                [$this->_definition, $argv0],
                $argv
            );
        }

        return $query;
    }

    public function __isset($name)
    {
        return isset($this->_values[$name]);
    }

    public function __get($name)
    {
        return (isset($this->_values[$name])) ? $this->_values[$name] : null;
    }

    public static function load($definition)
    {
        if (isset(Definition::$list[$definition])) {
            return null;
        }

        $fileName = strtolower(self::PATH . DS . $definition . '.php');
        if (!file_exists($fileName) || !is_readable($fileName)) {
            return null;
        }

        include_once $fileName;

        Definition::$list[$definition]->normalize();
        $dependencies = Definition::$list[$definition]->dependencies();
        foreach ($dependencies as $value) {
            self::load($value);
        }
    }

    private function buildQuery($type, $table, $params)
    {
        $query = self::$dataSource->$type($table);

        foreach ($params as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }

    private function normalize(array &$params = [])
    {
        $params[$this->_definition->table] = [];

        foreach ($params as $key => $value) {
            if (preg_match('/^(select|from|limit|where)$/i', $key, $matches)) {
                $params[$this->_definition->table][$matches[1]] = $value;
                unset($params[$key]);
            }

            if (preg_match(
                '/^(select|from|limit|where)\.(.*)/i',
                $key,
                $matches
            )
            ) {
                $params[$matches[2]][$matches[1]] = $value;
                unset($params[$key]);
            }
        }

        return $params;
    }

    public function clear()
    {
        $this->_values = [];

        $fields = array_filter(
            $this,
            function ($value) {
                return ($value instanceof Object);
            }
        );
        foreach ($fields as $key => $value) {
            if (isset($value->default)) {
                $value = $value->default;
                if ($value instanceof Closure) {
                    continue;
                }

                $this->_values[$key] = $value;
            }
        }

        return $this;
    }

    public function create(array $data = [])
    {
        return $this
            ->clear()
            ->set($data);
    }

    public function find($query, array $params = [])
    {
        $definition = $this->_definition;
        $params = $this->normalize($params);
        $dependencies = array_intersect(
            $definition->dependencies(),
            (isset($params['dependencies'])) ? $params['dependencies'] : []
        );
        $PK = $definition->key;
        $table = $definition->table;
        $fields = array_filter(
            $definition,
            function ($value) {
                return ($value instanceof Object)
                && !($value->type & Definition::REFERENCE);
            }
        );

        // normalize params and settings query

        switch ($query) {
        case 'first':
            $params[$table]['limit'] = 1;
            break;

        case 'all':
            break;

        default:
            $params[$table]['where']["`{$table}`.`{$PK}`"] = $query;
            break;
        }

        $params[$table]['select'] = array_merge(
            array_map(
                function ($value) use ($params, $table) {
                    if (isset($params[$table]['from'])) {
                        $value = "`{$table}`.`{$value}`";
                    }

                    return $value;
                },
                $fields
            ),
            $params[$table]['select']
        );

        $query = $this->buildQuery('select', $table, $params[$table]);

        // start method

        $definition->beforeFind((string) $query);

        $response = $query->execute();
        $data = [];

        // if response is affirmative

        if ($response->affectedRows) {

            // settings method response

            $keys = array_unique(\Vendor\array_column($response, $PK));

            foreach ($response as $value) {
                $data[$value[$PK]] = $value;
            }

            // quering dependencies

            foreach ($dependencies as $key => $value) {
                if (!isset(Definition::$list[$value])) {
                    continue;
                }

                $model = Definition::$list[$value];
                $rkey = $definition[$key]->key;
                $rtable = $definition[$key]->table;

                $params[$rtable]['dependencies'] = $params['dependencies'];
                $params[$rtable]['select'][] = "`{$rtable}`.`{$rkey}`";
                $params[$rtable]['where']["`{$rtable}`.`{$rkey}`"] = $keys;

                if (isset($definition[$key]->associationKey)) {
                    $associationKey = $definition[$key]->associationKey;
                    $params[$rtable]['from'][] = $rtable;
                    $params[$rtable]['where'][]
                        = "`{$rtable}`.`{$associationKey}` "
                        . "= `{$model->table}`.`{$model->key}`";

                    if (isset($definition[$key]->extra)) {
                        foreach ($definition[$key]->extra as $rvalue) {
                            $params[$rtable]['select'][]
                                = "`{$rtable}`.`{$rvalue}`";
                        }
                    }
                }

                $associated = (new self($model))
                    ->find('all', $params[$rtable]);

                foreach ($associated as $rvalue) {
                    $data[$rvalue[$value]->$rkey][$key][] = $rvalue;
                }
            }
        }

        if ($query != 'all') {
            $data = array_shift($data);

            if (isset($data)) {
                $this->create($data);
            }
        }

        $definition->afterFind($data);

        return $data;
    }

    public function getIterator()
    {
        return new ArrayIterator(array_merge($this->_values, $this->_extra));
    }

    public function jsonSerialize()
    {
        return array_merge($this->_values, $this->_extra);
    }

    public function remove($ID = null, $cascade = false, array $params = [])
    {
        $definition = $this->_definition;
        $params = $this->normalize($params);
        $values = $this->_values;
        $PK = $definition->key;
        $table = $definition->table;

        $definition->beforeDelete();

        $params[$table]['where'][$PK] = (isset($ID))
            ? $ID
            : ((isset($values[$PK])) ? $values[$PK] : '');

        $this->buildQuery(
            'delete',
            $definition->table,
            $params[$table]
        )
            ->execute();

        if ($cascade) {
            foreach ($definition->dependencies() as $key => $value) {
                $rkey = $definition[$key]->key;
                $rtable = $definition[$key]->table;

                if (!isset($params[$rtable])) {
                    $params[$rtable] = [];
                }

                foreach ($values[$key] as $rvalue) {
                    $rvalue->delete();
                }

                if (isset($definition[$key]->associationKey)) {
                    $params[$rtable]['where'][$rkey] = $ID;

                    $this->buildQuery(
                        'delete',
                        $rtable,
                        $params[$rtable]
                    )
                        ->execute();
                }
            }
        }

        if ($ID == $values[$definition->key]) {
            $this->clear();
        }

        $definition->afterDelete();
    }

    public function save(array $params = [])
    {
        $definition = $this->_definition;
        $params = $this->normalize($params);
        $values = $this->_values;
        $PK = $definition->key;
        $table = $definition->table;

        $fields = array_filter(
            $definition,
            function ($value) {
                return ($value instanceof Object)
                && !($value->type & Definition::REFERENCE);
            }
        );
        array_walk(
            $fields,
            function (&$value, &$key) use ($values) {
                if (isset($value->alias)) {
                    $key = $value->alias;
                }

                $value = $values[$key];
            }
        );
        $params[$table] = array_merge($fields, $params[$table]);

        $definition->beforeSave();

        if ($validate = $this->validate()) {
            throw new ErrorException($validate);
        }

        // generate query

        $create = !isset($values[$PK]);

        if (!$create) {
            $params[$table]['where'][$PK] = $values[$PK];
            unset($params[$table][$PK]);
        }

        $query = $this->buildQuery(
            ($create) ? 'insert' : 'update',
            $table,
            $params[$table]
        );

        // execute query

        try {
            $query->execute();
        }
        catch (Exception $e) {
            if ($create) {
                $create = false;

                foreach ($fields as $key => $value) {
                    $query->onDuplicateKey("`{$key}` = VALUES(`{$key}`)");
                }
            }
            $this->_values[$PK] = $values[$PK] = self::$dataSource->insertID();
        }

        if ($values[$PK] === 0) {
            $response = $this->buildQuery('select', $table, $fields)
                ->execute();

            if ($response->affectedRows) {
                $this->_values[$PK] = $values[$PK] = $response[$PK];
            }
        }

        // references

        $dependencies = $definition->dependencies();
        foreach ($dependencies as $key => $value) {
            if (!isset(Definition::$list[$value]) || !isset($values[$key])) {
                continue;
            }

            $model = Definition::$list[$value];

            array_walk(
                $values[$key],
                function ($rvalue) use (
                    $definition,
                    $key,
                    $model,
                    &$params,
                    $PK,
                    $values
                ) {
                    $complex = isset($definition[$key]->associationKey);
                    if (!$complex) {
                        $rvalue->set($definition[$key]->key, $values[$PK]);
                    }
                    $rvalue->save();

                    if ($complex) {
                        $rtable = $definition[$key]->table;
                        $params[$rtable][$definition[$key]->key] = $values[$PK];
                        $params[$rtable][$definition[$key]->associationKey]
                            = $model->key;

                        $params[$rtable] = array_merge(
                            $params[$rtable],
                            $rvalue->_extra
                        );

                        if (!empty($rvalue->_extra)) {
                            foreach ($rvalue->_extra as $rrkey => $rrvalue) {
                                $params[$rtable]['onDuplicateKey'][]
                                    = "`{$rrkey}` = VALUES(`{$rrkey}`)";
                            }
                        }

                        $this->buildQuery('insert', $rtable, $params[$rtable])
                            ->execute();
                    }
                }
            );
        }

        $definition->afterSave($values[$PK] && $create);
    }

    public function set($name, $value = null)
    {
        $definition = $this->_definition;

        if ((is_array($name) || is_object($name)) && !isset($value)) {
            foreach ($name as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            $name = (string) $name;

            if (isset($definition[$name])) {
                $type = $definition[$name]->type;

                if ($type & Definition::REFERENCE) {
                    $reference = $definition[$name]->reference;

                    if (!isset(Definition::$list[$reference])) {
                        return $this;
                    }

                    if (is_object($value)) {
                        $value = [$value];
                    } elseif (is_array($value)) {
                        $keys = array_keys($value);
                        if (!is_integer(array_shift($keys))) {
                            $value = [$value];
                        }
                    }

                    foreach ($value as $rvalue) {
                        $this->_values[$name][]
                            = (new self(Definition::$list[$reference]))
                            ->create($rvalue);
                    }

                    return $this;
                }

                $this->_values[$name] = $value;
            } else {
                $response = $this->query("DESCRIBE `{$definition['table']}`;")
                    ->execute();

                if (isset($response->num_rows) && $response->num_rows) {
                    $this->_values[$name] = $value;
                } else {
                    $this->_extra[$name] = $value;
                }
            }
        }

        return $this;
    }

    public function validate()
    {
        $this->call('beforeValidate');

        $response = $this->_definition->validate($this->_values);

        $this->call('afterValidate');

        return $response;
    }

}