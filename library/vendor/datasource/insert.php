<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.0
 *              Constructor modified, param $table removed.
 *              Add escape string to method value.
 *
 * @version     1.1 (08-18-2012)
 *              Arguments checking.
 *
 * @version     1.2 (09/07/2013)
 *              Implemented update on duplicate key.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\DataSource;

use Vendor\DataSource;

class Insert extends Query
{

    protected $into;
    protected $column = array();
    protected $onDuplicateKey = array();
    protected $value = array();

    public function __construct(DataSource $link)
    {
        parent::__construct(
            $link,
            'INSERT INTO {into} {COLUMN}VALUES ({value}) {ONDUPLICATEKEY};'
        );
    }

    public function __call($argv0, $argv)
    {
        $this
            ->column($argv0)
            ->value($argv[0]);

        return $this;
    }

    public function __toString()
    {
        $this->replace('into', $this->into);

        if (!empty($this->column)) {
            $this->replace(
                'COLUMN',
                '(' . implode(', ', $this->column) . ') '
            );
        }

        if (!empty($this->value)) {
            $value = implode(
                '), (',
                array_reduce(
                    array_chunk($this->value, count($this->column)),
                    function ($result, $item) {
                        $result[] = implode(', ', $item);

                        return $result;
                    },
                    array()
                )
            );

            $this->replace('value', $value);
        }

        if (!empty($this->onDuplicateKey)) {
            $this->replace(
                'ONDUPLICATEKEY',
                'ON DUPLICATE KEY UPDATE ' . implode(
                    ', ',
                    $this->onDuplicateKey
                )
            );
        }

        return parent::__toString();
    }

    public function table()
    {
        return $this->into;
    }

    public function into($table)
    {
        $this->into = str_replace('``', '`', "`{$table}`");

        return $this;
    }

    public function column($column)
    {
        if (is_array($column) || is_object($column)) {
            foreach ($column as $value) {
                $this->column($value);
            }
        } else {
            $this->column[] = "`{$column}`";
        }

        return $this;
    }

    public function onDuplicateKey($name, $value = null)
    {
        if (is_array($name) || is_object($name)) {
            foreach ($name as $key => $value) {
                $this->onDuplicateKey($key, $value);
            }
        } else {
            if (isset($value)) {
                $name = $this->escape("`{$name}` = ?", $value);
            }
            $this->onDuplicateKey[] = $name;
        }

        return $this;
    }

    public function value($value)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $rvalue) {
                $this->value($rvalue);
            }
        } else {
            $this->value[] = "'" . $this->link->escape($value) . "'";
        }

        return $this;
    }
}
