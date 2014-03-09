<?php

/**
 * Base Query Class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2012 La Cuarta Edad
 *
 */

namespace Vendor\DataSource;

use Vendor\DataSource;

class Query
{

    private $statement;
    private $replacement = array();

    protected $link;

    public function __construct(DataSource $link, $statement)
    {
        $argv = func_get_args();

        $this->link = $link;
        array_shift($argv);

        call_user_func_array(array($this, 'statement'), $argv);
    }

    public function __toString()
    {
        $pattern = '/\{([a-zA-Z0-9\-_]+)\}/i';
        $replacement = $this->replacement;

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($replacement) {
                $key = $matches[1];

                return (isset($replacement[$key])) ? $replacement[$key] : '';
            },
            $this->statement
        );
    }

    final protected function escape($statement)
    {
        $argv = func_get_args();
        $statement = preg_replace('/\?/', "'?'", $statement, count($argv) - 1);

        $k = 1;
        $pattern = '/\?/';

        return preg_replace_callback(
            $pattern,
            function () use ($argv, &$k) {
                return $this->link->escape($argv[$k++]);
            },
            $statement,
            count($argv) - 1
        );
    }

    final protected function replace($key, $value)
    {
        $this->replacement[$key] = $value;
    }

    final protected function statement()
    {
        $this->statement = call_user_func_array(
            array($this, 'escape'),
            func_get_args()
        );
    }

    public function execute()
    {
        return $this->link->query($this);
    }

    public function table()
    {
        return null;
    }
}
