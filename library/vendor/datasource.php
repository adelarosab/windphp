<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.0 (11/03/2012)
 *              Add funcionality to delete:
 *              Can delete rows of a before select.
 *              Can delete rows of a before update or insert.
 *              Add funcionality to insert:
 *              Can add data through a object.
 *              Can add data through an array.
 *              Checking after every connection.
 *              Checking constructor params.
 *
 * @version     1.1 (08/26/2013)
 *              Delete now use the primary key instead of id.
 *
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor;

use Vendor\DataSource\Delete;
use Vendor\DataSource\Insert;
use Vendor\DataSource\Query;
use ReflectionClass;
use Vendor\DataSource\Select;
use Vendor\DataSource\Update;

abstract class DataSource
{

    public static $DEBUG = DEBUG_NONE;

    protected $link;

    private $last;
    private $lastResponse;
    private $lastTable;

    public function __construct($link)
    {
        $this->link = $link;
    }

    public function delete($table = null)
    {
        $deleteAfter = array('insert', 'select', 'update');
        $lastAction = strtolower(basename(get_class($this->last)));
        $canUseLast = isset($this->last) && in_array($lastAction, $deleteAfter);

        if (!isset($table) && $canUseLast) {
            $describe = $this
                ->statement(
                    "SHOW KEYS FROM {$this->lastTable} "
                    . 'WHERE `key_name` = \'PRIMARY\''
                )
                ->execute();

            $delete = (new Delete($this))
                ->from($this->lastTable);

            switch ($lastAction) {
                case 'insert':
                case 'update':
                    foreach ($describe as $value) {
                        $delete->where(
                            array($value->Column_name => $this->lastResponse)
                        );
                    }
                    break;

                case 'select':
                    $primaryKey = array_reduce(
                        $this->lastResponse,
                        function ($result, $item) use ($describe) {
                            foreach ($describe as $value) {
                                $result[$value->Column_name][]
                                    = $item->{$value->Column_name};
                            }

                            return $result;
                        },
                        array()
                    );

                    if (empty($primaryKey)) {
                        return $this;
                    }

                    foreach ($primaryKey as $key => $value) {
                        $delete->where(array($key => $value));
                    }
                    break;
            }

            $delete->execute();

            return $this;
        }

        $delete = new Delete($this);
        if (isset($table)) {
            $delete->from($table);
        }

        return $delete;
    }

    public function escape($value)
    {
        return $value;
    }

    public function insert($table = null, $data = null)
    {
        if (is_object($table) || is_array($table) || isset($data)) {
            $insert = new Insert($this);

            $table = (isset($data)) ? $table : $this->lastTable;
            $data = (isset($data)) ? $data : $table;

            foreach ($data as $key => $value) {
                $insert->$key($value);
            }

            if (!isset($table)) {
                return $insert;
            }

            $insert
                ->into($table)
                ->execute();

            return $this;
        }

        $table = (isset($table)) ? $table : $this->lastTable;

        $insert = new Insert($this);
        if (isset($table)) {
            $insert->into($table);
        }

        return $insert;
    }

    public function query(Query $query)
    {
        $response = $this->link->{'query'}((string) $query);

        if (self::$DEBUG & DEBUG_DATABASE) {
            print str_pad('', 75, '=') . PHP_EOL;
            print 'DATABASE' . PHP_EOL;
            print str_pad('', 75, '=') . PHP_EOL;
            print $query . PHP_EOL;
            print str_pad('', 75, '*') . PHP_EOL;
        }

        $this->last = $query;
        $this->lastResponse = $response;
        $this->lastTable = $query->table();

        return $this->lastResponse;
    }

    public function select($table = null)
    {
        $table = (isset($table)) ? $table : $this->lastTable;

        $select = new Select($this);
        if (isset($table)) {
            $select->from($table);
        }

        return $select;
    }

    public function statement()
    {
        $argv = func_get_args();
        array_unshift($argv, $this);

        $reflect = new ReflectionClass('Vendor\DataSource\Query');

        return $reflect->newInstanceArgs($argv);
    }

    public function update($table = null)
    {
        $table = (isset($table)) ? $table : $this->lastTable;

        $update = new Update($this);
        if (isset($table)) {
            $update->from($table);
        }

        return $update;
    }
}