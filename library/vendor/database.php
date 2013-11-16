<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   1.0 (11/03/2012)
 *            Add funcionality to delete:
 *            Can delete rows of a before select.
 *            Can delete rows of a before update or insert.
 *            Add funcionality to insert:
 *            Can add data through a object.
 *            Can add data through an array.
 *            Checking after every connection.
 *            Checking constructor params.
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor;

use ErrorException;
use Vendor\DataSource\Query;
use mysqli;
use Vendor\DataSource\Response;

class DataBase extends DataSource
{

    private $database;

    public function __construct($host, $user, $password, $database)
    {
        $this->link = new mysqli($host, $user, $password, $database);
        $this->database = $database;

        if ($this->link->connect_errno) {
            throw new ErrorException($this->link->connect_error);
        }

    }

    public function __destruct()
    {
        $this->link->close();
    }

    public function getDatabase()
    {
        return (string) $this->database;
    }

    private function check()
    {
        if ($this->link->errno) {
            throw new ErrorException($this->link->error);
        }
    }

    public function changeUser($user, $password)
    {
        $this->link->change_user($user, $password, $this->database);
        $this->check();

        return $this;
    }

    public function escape($value)
    {
        return $this->link->escape_string($value);
    }

    public function insertID()
    {
        return $this->link->insert_id;
    }

    public function query(Query $query)
    {
        $response = parent::query($query);
        $this->check();

        $class = strtolower(basename(get_class($query)));
        switch ($class) {
        case 'insert':
        case 'update':
            $response = $this->link->insert_id;
            break;

        case 'select':
            $data = array();
            while ($row = $response->fetch_assoc()) {
                $data[] = $row;
            }
            $response = new Response($response);
            break;

        default:
            break;
        }

        return $response;
    }

    public function selectDB($name)
    {
        $this->link->select_db($name);
        $this->check();

        return $this;
    }
}
