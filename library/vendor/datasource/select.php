<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.2 (03/03/2012)
 *              Constructor modified, param $table removed.
 *
 * @version     1.3 (04/10/2012) (T_T RMS Titanic Set Sail)
 *              Group by added.
 *
 * @version     1.4 (08/31/2013)
 *              Every method now support array input.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\DataSource;

use Vendor\DataSource;

class Select extends Delete
{

    protected $column = array();
    protected $groupBy = array();

    public function __construct(DataSource $link)
    {
        parent::__construct($link);

        $this->statement(
            'SELECT {column} FROM {from} {WHERE}{GROUPBY}{ORDER}{LIMIT};'
        );
    }

    public function __call($argv0, $argv)
    {
        if (count($argv) == 0) {
            return $this->select($argv0);
        }

        return parent::__call($argv0, $argv);
    }

    public function __toString()
    {
        $this->replace(
            'column',
            (!empty($this->column)) ? implode(', ', $this->column) : '*'
        );

        if (!empty($this->groupBy)) {
            $this->replace(
                'GROUPBY',
                'GROUP BY ' . implode(', ', $this->groupBy) . ' '
            );
        }

        return parent::__toString();
    }

    public function groupBy($column)
    {
        $this->groupBy[] = str_replace('``', '`', "`{$column}`");

        return $this;
    }

    public function limit($size, $offset = 0)
    {
        $this->limit = array($offset, $size);

        return $this;
    }

    public function select($column, $alias = null)
    {
        if (is_array($column) || is_object($column)) {
            $method = __METHOD__;
            array_walk(
                $column,
                function ($value, $key) use ($method, $this) {
                    $value = array($value);

                    if (is_string($key)) {
                        array_unshift($value, $key);
                    }

                    call_user_func_array(array($this, $method), $value);
                }
            );
        } else {
            $select = str_replace('``', '`', "`{$column}`");

            if (isset($alias)) {
                $select .= " AS '{$alias}'";
            }

            $this->column[] = $select;
        }

        return $this;
    }
}
