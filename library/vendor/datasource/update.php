<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.1 (03/03/2012)
 *              Constructor modified, param $table removed.
 *              Add escape string to method change.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\DataSource;

use Vendor\DataSource;

class Update extends Delete
{

    protected $change = array();

    public function __construct(DataSource $link)
    {
        parent::__construct($link);
        $this->statement(
            'UPDATE {from} SET {change} {WHERE}{ORDER}{LIMIT};'
        );
    }

    public function __call($argv0, $argv)
    {
        return $this->change($argv0, $argv[0]);
    }

    public function __toString()
    {
        $this->replace('change', implode(', ', $this->change));

        return parent::__toString();
    }

    public function change($name, $value = null)
    {
        if (is_array($name) || is_object($name)) {
            foreach ($name as $value) {
                $this->change($value);
            }
        } else {
            $change = str_replace('``', '`', "`{$name}`");
            $value = "'" . $this->link->escape($value) . "'";

            $this->change[] = $change . ' = ' . $value;
        }

        return $this;
    }
}
