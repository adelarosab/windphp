<?php

/**
 * Delete Class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2012 La Cuarta Edad
 *
 * @version     1.0 (03/03/2012)
 *              Constructor modified, param $table removed.
 *              Modify functions where and orWhere to substitute ? param.
 *
 * @version     1.3 (04/10/2012) (T_T RMS Titanic Set Sail):
 *              Multiple table.
 *
 * @version     1.4 (08-18-2012)
 *              Arguments checking.
 *
 * @version     1.5 (08/31/2013)
 *              Every method now support array input.
 *
 * @version     1.6 (09/14/2013)
 *              Support array @ order method.
 *
 */

namespace Vendor\DataSource;

use Vendor\DataSource;

class Delete extends Query
{

    protected $from = array();
    protected $limit;
    protected $order = array();
    protected $where = array();

    public function __construct(DataSource $link)
    {
        parent::__construct(
            $link,
            'DELETE FROM {from} {WHERE}{ORDERBY}{LIMIT};'
        );
    }

    public function __call($argv0, $argv)
    {
        if (is_array($argv[0])) {
            $argv = $argv[0];
        }

        $column = str_replace('``', '`', "`{$argv0}`");
        $value = implode(', ', array_fill(0, count($argv), '?'));
        $condition = $column
            . ((count($argv) < 2) ? "= {$value}" : "IN ({$value})");

        array_unshift(
            $argv,
            $condition
        );

        return call_user_func_array(array($this, 'where'), $argv);
    }

    public function __toString()
    {
        $this->replace(
            'from',
            implode(
                ', ',
                $this->from
            )
        );

        if (!empty($this->where)) {
            $this->replace(
                'WHERE',
                'WHERE ' . implode(' ', array_slice($this->where, 1)) . ' '
            );
        }


        if (!empty($this->order)) {
            $this->replace(
                'ORDERBY',
                'ORDER BY ' . implode(', ', $this->order)
            );
        }

        if (isset($this->limit)) {
            $this->replace('LIMIT', "LIMIT {$this->limit}");
        }

        return parent::__toString();
    }

    private function _where($method)
    {
        $argv = array_slice(func_get_args(), 1);

        if (isset($argv[0]) && is_array($argv[0]) || is_object($argv[0])) {
            array_pop($this->where);
            array_walk(
                $argv,
                function ($value, $key) use ($method, $this) {
                    if (is_string($key)) {
                        array_unshift($value, "`{$key}` = ?");
                    }

                    call_user_func_array(array($this, $method), $value);
                }
            );
        } else {
            $this->where[] = call_user_func_array(
                array($this, 'escape'),
                $argv
            );
        }
    }

    public function from($table, $alias = null)
    {
        if (is_array($table) || is_object($table)) {
            $method = __METHOD__;
            array_walk(
                $table,
                function ($value, $key) use ($method, $this) {
                    $value = array($value);

                    if (is_string($key)) {
                        array_unshift($value, $key);
                    }

                    call_user_func_array(array($this, $method), $value);
                }
            );
        } else {
            $from = str_replace('``', '`', "`{$table}`");
            if (isset($alias)) {
                $from .= " AS '{$alias}'";
            }

            $this->from[] = $from;
        }

        return $this;
    }

    public function limit($size)
    {
        $this->limit = $size;

        return $this;
    }

    public function orderBy($column, $asc = true)
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
            $asc = (is_bool($asc)) ? (($asc) ? 'ASC' : 'DESC') : $asc;
            $order = "`{$column}` {$asc}";

            $this->order[] = $order;
        }

        return $this;
    }

    public function orWhere($condition)
    {
        $this->where[] = 'OR';
        call_user_func_array(array($this, '_where'), func_get_args());

        return $this;
    }

    public function table()
    {
        return $this->from[0];
    }

    public function where($condition)
    {
        $this->where[] = 'AND';
        call_user_func_array(array($this, '_where'), func_get_args());

        return $this;
    }
}
