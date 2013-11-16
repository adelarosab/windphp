<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      2.0 (10/05/2012)
 *               Remove HEADERS, METHOD Class.
 * @version      2.1 (02/14/2013)
 *               Debug added.
 * @version      2.2 (02/20/2013)
 *               Destruct function added. Now the connection is closed at end.
 *
 * @version      3.0 (05/20/2013)
 *
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor;

use ErrorException;
use Vendor\URL\Request;
use Vendor\URL\Response;

class URL extends Request
{

    public static $DEBUG = DEBUG_NONE;

    private $HANDLER = null;
    private $defaultOptions = array();

    public function __construct()
    {
        parent::__construct();

        if (!function_exists('curl_init')) {
            throw new ErrorException("CURL extension not supported");
        }

        $this->HANDLER = curl_init();
        $this->method(self::GET);
    }

    public function __destruct()
    {
        curl_close($this->HANDLER);
    }

    protected function reset()
    {
        $this->__destruct();
        $this->HANDLER = curl_init();

        parent::reset();
        $this->option($this->defaultOptions);
    }

    public function fetch(
        $url = null,
        $method = null,
        array $data = array(),
        array $options = array()
    ) {
        $this->url((isset($url)) ? $url : $this->url());
        $this->method((isset($method)) ? $method : $this->method());
        if (!empty($data)) {
            $this->data($data);
        }

        $options = $options + $this->build();
        curl_setopt_array($this->HANDLER, $options);

        if (self::$DEBUG & DEBUG_URL) {
            print str_pad('', 75, '=') . PHP_EOL;
            print 'URL' . PHP_EOL;
            print str_pad('', 75, '=') . PHP_EOL;
            print 'URL: ' . $this->url() . PHP_EOL;
            print 'METHOD: ' . $this->method() . PHP_EOL;
            print 'DATA: ' . print_r($this->data(), true) . PHP_EOL;
            print str_pad('', 75, '*') . PHP_EOL;
        }

        $return = curl_exec($this->HANDLER);
        $info = curl_getinfo($this->HANDLER);
        $this->reset();

        if ($options[CURLOPT_RETURNTRANSFER]) {
            return new Response(
                $return,
                $info
            );
        }

        return null;
    }

    public function setDefaultOption($name, $value)
    {
        $this->option($name, $value);
        $this->defaultOptions[$name] = $value;
    }
}
