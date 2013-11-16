<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/02/2013)
 *
 * @version      2.0 (08/05/2013)
 *               Remake.
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use UnderflowException;

class View
{

    const PATH = APP_VIEW;

    public static $dictionary = array();

    private $fileName;
    private $replacement = array();

    public function __construct($file)
    {
        if (!file_exists($file) && is_readable($file)) {
            throw new UnderflowException("{$file} not such file");
        }

        $this->fileName = $file;
    }

    public function __toString()
    {
        return $this->replace(file_get_contents($this->fileName));
    }

    public static function fromClassName($className)
    {
        if (is_array($className)) {
            $className = implode(DIRECTORY_SEPARATOR, $className);
        }

        $file = strtolower(
            self::PATH . DIRECTORY_SEPARATOR . $className . '.tpl'
        );

        if (!file_exists($file)) {
            return null;
        }

        return new self($file);
    }

    private function dictionary($dictionary = null)
    {
        if (!isset($dictionary)) {
            return self::$dictionary;
        }

        self::$dictionary = $dictionary;

        return $this;
    }

    private function get($key)
    {
        $key = $key[1]; // callback preg_replace_callback

        if (preg_match('/language\.(.*)/i', $key, $matches)) {
            $dictionary = $this->dictionary();
            $return = strtolower($matches[1]);
            $word = $matches[1];

            $return = (isset($dictionary[$return])) ? $dictionary[$return] : '';

            switch (true) {
                case \Vendor\is_capital($word):
                    $return = ucfirst($return);
                    break;

                case \Vendor\is_uppercase($word):
                    $return = strtoupper($return);
                    break;
            }

            $this->set($key, $return);
        }

        return (isset($this->replacement[$key]))
            ? $this->replacement[$key]
            : '';
    }

    protected function replace($subject)
    {
        $pattern = '/{{\s*([a-z_.]+)\s*}}/i';
        $replace = array($this, 'get');

        return preg_replace_callback($pattern, $replace, $subject);
    }

    public function set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $rkey => $rvalue) {
                $this->set("{$key}.{$rkey}", $rvalue);
            }
        } else {
            $this->replacement[$key] = $value;
        }
    }

}