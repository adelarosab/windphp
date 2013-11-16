<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/02/2013)
 * @version      1.1 (08/27/2013)
 *               Normalize definition object before any operation.
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use ArrayIterator;
use ErrorException;
use Vendor\Object;

class Model extends Object
{

    const PATH = APP_MODEL;
    const PRIMARY_KEY = 'ID';

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

    public static $dataSource = null;
    public static $list = array();

    private $name;
    private $values = array();

    public function __construct($name)
    {
        parent::__construct();
        $this->name = $name;
        self::$list[$name] = $this;

        $this->member[self::PRIMARY_KEY] = (new Object)
            ->type(
                self::INT
            );
    }

    public function __call($argv0, $argv)
    {
        switch ($argv0) {
        case 'delete':
        case 'insert':
        case 'select':
        case 'update':
            $table = (isset($argv[0])) ? $argv[0] : $this->member['table'];
            $query = self::$dataSource->{$argv0}($table);
            break;

        case 'query':
            $query = call_user_func_array(
                array(self::$dataSource, 'statement'),
                $argv
            );
            break;

        default:
            return parent::__call($argv0, $argv);
        }

        return $query;
    }

    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    public function __get($name)
    {
        return (isset($this->values[$name])) ? $this->values[$name] : null;
    }

    public static function load($className)
    {
        if (isset(self::$list[$className])) {
            return null;
        }

        $fileName = strtolower(
            self::PATH . DIRECTORY_SEPARATOR . $className . '.php'
        );

        if (!file_exists($fileName) || !is_readable($fileName)) {
            return null;
        }

        require_once $fileName;

        foreach (self::$list[$className]->{'dependencies'}(false) as $value) {
            self::load($value);
        }

        return null;
    }

    private function normalizeDefinition()
    {
        // attributes
        foreach ($this->member as &$value) {
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

                if (!isset($value->key)) {
                    $value->key = strtolower($this->name) . self::PRIMARY_KEY;
                }

                if (!isset($value->table)
                    && isset($reference)
                    && isset(Model::$list[$reference])
                    && isset(Model::$list[$reference]->member['table'])
                ) {
                    $value->table = Model::$list[$reference]->member['table'];
                }
            }
        }

        // general properties of model
        if (!isset($this->member['key'])) {
            $this->member['key'] = self::PRIMARY_KEY;
        }

        if (!isset($this->member['table'])) {
            $this->member['table'] = strtolower($this->name);
        }
    }

    private function normalizeParams(array &$params = array())
    {
        $params[$this->name] = array();

        foreach ($params as $key => $value) {
            if (preg_match('/^(select|from|limit|where)$/i', $key, $matches)) {
                $params[$this->name][$matches[1]] = $value;
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
    }

    public function afterDelete()
    {
    }

    public function afterFind($response)
    {
    }

    public function afterSave($created)
    {
    }

    public function afterValidate()
    {
    }

    public function beforeDelete()
    {
    }

    public function beforeFind($query)
    {
    }

    public function beforeSave()
    {
    }

    public function beforeValidate()
    {
    }

    public function clear()
    {
        $this->values = array();

        foreach ($this->member as $key => $value) {
            if (!($value instanceof Object)) {
                continue;
            }

            if (isset($value->default)) {
                $this->values[$key] = $value->default;
            }
        }

        return $this;
    }

    public function create($data = array())
    {
        return $this
            ->clear()
            ->set($data);
    }

    public function delete($ID = null, $cascade = true)
    {
        $this->beforeDelete();

        $this->normalizeDefinition();
        $definition = $this->member;
        $values = $this->values;

        $delete = $this->delete();
        if (!isset($ID) && isset($values[self::PRIMARY_KEY])) {
            $ID = $values[self::PRIMARY_KEY];
        }

        if (isset($ID)) {
            $delete->where(array(self::PRIMARY_KEY => $ID));
        }

        $delete->execute();

        if ($cascade) {
            foreach ($this->dependencies(false) as $key => $value) {
                foreach ($values[$key] as $rvalue) {
                    $rvalue->delete();
                }

                if (isset($definition[$key]->associationKey)) {
                    $this->delete($definition[$key]->table)
                        ->where(array($definition[$key]->key => $ID))
                        ->execute();
                }
            }
        }

        $this->clear();

        $this->afterDelete();
    }

    public function dependencies($greedy = true, $cascade = array())
    {
        $dependencies = array();

        $this->normalizeDefinition();
        $definition = $this->member;

        foreach ($definition as $key => $value) {
            $reference = $value->reference;
            $type = $value->type;

            if (
                ($value instanceof Object)
                && ($type & self::REFERENCE)
                && !in_array($reference, $cascade)
            ) {
                $cascade[] = $reference;
                $dependencies[$key] = $reference;

                if ($greedy) {
                    Model::load($reference);

                    if (!isset(Model::$list[$reference])) {
                        continue;
                    }

                    array_merge(
                        $dependencies,
                        array_values(
                            self::$list[$reference]->dependencies(
                                $greedy,
                                $cascade
                            )
                        )
                    );
                }
            }
        }

        if ($greedy) {
            $dependencies = array_values($dependencies);
        }

        asort($dependencies);

        return $dependencies;
    }

    public function find($query, $params = array())
    {
        $this->normalizeDefinition();
        $this->normalizeParams($params);
        $definition = $this->member;

        // normalize params and settings query

        $select = $this->select($definition['table']);
        switch ($query) {
        case 'first':
            $params[$this->name]['limit'] = 1;
            break;

        case 'all':
            break;

        default:
            $params[$this->name]['where'][
            "`{$definition['table']}`.`" . self::PRIMARY_KEY . '`']
                = $query;
            break;
        }

        foreach ($params[$this->name] as $key => $value) {
            call_user_func(array($select, $key), $value);
        }

        $prefix = '';
        if (isset($params[$this->name]['from'])) {
            $prefix = "`{$definition['table']}`.";
        }

        foreach ($definition as $key => $value) {
            if (!($value instanceof Object)
                && ($value->type & self::REFERENCE)
            ) {
                continue;
            }

            $select->select("{$prefix}`{$key}`");
        }

        // start method

        $this->beforeFind((string) $select);

        $response = $select->execute();

        $data = array();

        // if response is affirmative

        $dependencies = $this->dependencies(false);
        if (isset($params['dependencies'])) {
            $dependencies = array_intersect(
                $dependencies,
                $params['dependencies']
            );
        }

        if ($response->affectedRows) {

            // settings method response

            $primaryKey = array();
            foreach ($response as $value) {
                $primaryKey[] = $value->{self::PRIMARY_KEY};
            }
            $primaryKey = array_unique($primaryKey);

            $data = array();
            foreach ($response as $value) {
                $data[$value->{self::PRIMARY_KEY}][$this->name] = $value;
            }

            // quering dependencies

            foreach ($dependencies as $key => $value) {
                if (!isset(Model::$list[$value])) {
                    continue;
                }

                $rkey = $definition[$key]->key;
                $table = $definition[$key]->table;

                $paramsSubQuery = array(
                    'select' => array("`{$table}`.`{$rkey}`"),
                    'where' => array("`{$table}`.`{$rkey}`" => $primaryKey)
                );

                if (isset($definition[$key]->associationKey)) {
                    $model = Model::$list[$value];
                    $model->normalizeDefinition();
                    $model = $model->member;

                    $paramsSubQuery['from'][] = $table;
                    $paramsSubQuery['where'][] = array(
                        "`{$table}`.`{$definition[$key]->associationKey}` "
                        . "= `{$model['table']}`.`" . self::PRIMARY_KEY . "`"
                    );
                }

                if (isset($params[$value])) {
                    $paramsSubQuery = array_merge_recursive(
                        $params[$value],
                        $paramsSubQuery
                    );
                }

                $associated = Model::$list[$value]
                    ->find(
                        'all',
                        $paramsSubQuery
                    );

                foreach ($associated as $rvalue) {
                    $data[$rvalue[$value]->{$rkey}][$value][]
                        = $rvalue;
                }
            }
        }

        if ($query != 'all') {
            $data = array_shift($data);

            if (isset($data)) {
                $this->create($data[$this->name]);
                foreach ($dependencies as $key => $value) {
                    if (isset($data[$value])) {
                        $this->set($key, $data[$value]);
                    }
                }
            }
        }

        $this->afterFind($data);

        return $data;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->values);
    }

    public function jsonSerialize()
    {
        return $this->values;
    }

    public function save($params = array())
    {
        $this->beforeSave();

        $this->normalizeDefinition();
        $definition = $this->member;
        $values = $this->values;

        $this->normalizeParams($params);

        if (($validate = $this->validate())) {
            throw new ErrorException($validate);
        }

        // generate values

        $insert = array();
        foreach ($values as $key => $value) {
            if (
                isset($definition[$key])
                && ($definition[$key]->type & self::REFERENCE)
            ) {
                continue;
            }

            if (isset($definition[$key]->alias)) {
                $key = $definition[$key]->alias;
            }

            $insert[$key] = $value;
        }

        // generate query

        $create = !isset($insert[self::PRIMARY_KEY]);
        $query = null;
        if ($create) {
            $query = $this->insert($definition['table']);
        } else {
            $query = $this->update($definition['table'])
                ->where(array(self::PRIMARY_KEY => $insert[self::PRIMARY_KEY]));
            unset($insert[self::PRIMARY_KEY]);
        }

        foreach ($insert as $key => $value) {
            $query->{$key}($value);
        }

        foreach ($params[$this->name] as $key => $value) {
            call_user_func(array($query, $key), $value);
        }

        if ($create) {
            foreach ($insert as $key => $value) {
                $query->onDuplicateKey("`{$key}` = VALUES(`{$key}`)");
            }
        }

        // execute query

        if (!empty($insert)) {
            $query->execute();
            $this->values[self::PRIMARY_KEY]
                = $values[self::PRIMARY_KEY] = self::$dataSource->insertID();
        }

        if ($values[self::PRIMARY_KEY] === 0) {
            $select = $this->select($definition['table']);

            foreach ($insert as $key => $value) {
                $select->$key($value);
            }

            $response = $select->execute();

            if ($response->affectedRows) {
                $this->values[self::PRIMARY_KEY]
                    = $values[self::PRIMARY_KEY] = $response[self::PRIMARY_KEY];
            }
        }

        // references

        $dependencies = $this->dependencies(false);
        foreach ($dependencies as $key => $value) {
            if (!isset(Model::$list[$value]) || !isset($values[$key])) {
                continue;
            }

            foreach ($values[$key] as $rvalue) {
                if (!isset($definition[$key]->associationKey)) {
                    $rvalue->set(
                        $definition[$key]->key,
                        $values[self::PRIMARY_KEY]
                    );
                }
                $rvalue->save();

                if (isset($definition[$key]->associationKey)) {
                    $insert = $this
                        ->insert($definition[$key]->table)
                        ->{$definition[$key]->key}(
                            $values[self::PRIMARY_KEY]
                        )
                        ->{$definition[$key]->associationKey}(
                            $rvalue->{self::PRIMARY_KEY}
                        );

                    $extra
                        = (isset($params['extra'][$value][$rvalue->{self::PRIMARY_KEY}]))
                        ? $params['extra'][$value][$rvalue->{self::PRIMARY_KEY}]
                        : null;
                    if (isset($extra)) {
                        foreach ($extra as $rrkey => $rrvalue) {
                            call_user_func(array($insert, $rrkey), $rrvalue);
                        }
                    }

                    if (isset($params[$value])) {
                        foreach ($params[$value] as $rrkey => $rrvalue) {
                            call_user_func(array($insert, $rrkey), $rrvalue);
                        }
                    }

                    if (isset($extra)) {
                        foreach ($extra as $rrkey => $rrvalue) {
                            $insert->{'onDuplicateKey'}(
                                "`{$rrkey}` = VALUES(`{$rrkey}`)"
                            );
                        }
                    } else {
                        $insert->{'onDuplicateKey'}(
                            "`{$definition[$key]->key}` "
                            . "= VALUES(`{$definition[$key]->key}`)"
                        );
                    }

                    $insert->{'execute'}();
                }
            }
        }

        $this->afterSave($this->values[self::PRIMARY_KEY] && $create);
    }

    public function set($name, $value = null)
    {
        $this->normalizeDefinition();
        $definition = $this->member;

        if ((is_array($name) || is_object($name)) && !isset($value)) {
            foreach ($name as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            if (isset($definition[$name])) {
                $type = $definition[$name]->type;

                if ($type & self::REFERENCE) {
                    $reference = $definition[$name]->reference;

                    if (!isset(Model::$list[$reference])) {
                        return $this;
                    }

                    if (is_object($value)) {
                        $value = array($value);
                    } elseif (is_array($value)) {
                        $keys = array_keys($value);
                        if (!is_integer(array_shift($keys))) {
                            $value = array($value);
                        }
                    }

                    foreach ($value as $rvalue) {
                        $this->values[$name][]
                            = clone Model::$list[$type[1]]->create($rvalue);
                    }

                    return $this;
                }

                $this->values[$name] = $value;
            } else {
                $response = $this->query("DESCRIBE `{$definition['table']}`;")
                    ->execute();

                if (isset($response->num_rows) && $response->num_rows) {
                    $this->values[$name] = $value;
                }
            }
        }

        return $this;
    }

    public function validate()
    {
        $this->beforeValidate();

        $this->normalizeDefinition();
        $definition = $this->member;
        $values = & $this->values;


        foreach ($definition as $key => $value) {
            if (!($value instanceof Object)) {
                continue;
            }

            if (!isset($values[$key])) {
                if (
                    isset($value['validation'])
                    && $value['validation']->required
                ) {
                    return $value['validation']->required;
                }

                continue;
            }

            if ($value->type & self::REFERENCE) {
                foreach ($values[$key] as $rrvalue) {
                    if ($validation = $rrvalue->validate()) {
                        return $validation;
                    }
                }

                continue;
            }

            $rvalue = & $values[$key];

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

            if (!isset($value['validation'])) {
                continue;
            }

            $validation = $value['validation'];
            foreach ($validation as $rrkey => $rrvalue) {
                switch ($rrkey) {
                default:
                    $error = !preg_match($rrkey, $rvalue);
                    break;
                }

                if ($error) {
                    return $rrvalue;
                }
            }

        }

        $this->afterValidate();

        return null;
    }

}