<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/01/2013)
 * @version      2.0 (04/30/2013)
 *               Singleton pattern added. Change everything about routing.
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use Vendor\MVC\util\Singleton;

class Router extends Singleton
{

    public static $default = array();

    const REGEXP_ACTION = "[a-zA-Z]+";
    const REGEXP_CONTROLLER = self::REGEXP_ACTION;

    const HIGH = 1;
    const LOW = 2;
    const NORMAL = 4;

    private $table
        = array(
            self::LOW => array(),
            self::NORMAL => array(),
            self::HIGH => array()
        );

    private function match($URI)
    {
        $replacement = array_reverse(
            array_merge(
                $this->table[self::LOW],
                $this->table[self::NORMAL],
                $this->table[self::HIGH]
            )
        );

        $URI = parse_url($URI)['path'];
        $response = $this->parse($URI);

        foreach ($replacement as $key => $value) {
            if (preg_match($key, $URI, $matches)) {
                foreach ($value[1] as $rkey => $rvalue) {
                    $value[1][$rkey] = $matches[$rvalue];
                }

                $response = array_merge($response, $value[0], $value[1]);
                break;
            }
        }

        return $response;
    }

    private function parse($URI)
    {
        $URI = parse_url($URI);
        $path = (isset($URI['path'])) ? $URI['path'] : '/';

        $out = array_filter(explode('/', $path));

        return array_merge(
            array(
                'controller' => array_shift($out),
                'action' => array_shift($out)
            ),
            $out
        );
    }

    public static function getInstance()
    {
        $router = parent::getInstance();

        if (!empty(self::$default)) {
            foreach (self::$default as $key => $value) {
                $router->connect($key, $value);
            }
        }

        return $router;
    }

    public function connect(
        $regexp,
        array $defaults = array(),
        array $replace = array(),
        $priority = self::NORMAL
    ) {
        if (substr($regexp, -1, 1) == '*') {
            $regexp = substr($regexp, 0, -1);
            if (substr($regexp, -1, 1) == '/') {
                $regexp .= '?';
            }
        } else {
            $regexp .= '\/?$';
        }

        $params = array();
        $k = 0;
        foreach ($replace as $key => $value) {
            $regexp = preg_replace("/:$key/i", "($value)", $regexp);

            if ($key == 'controller' || $key == 'action') {
                $params[$key] = $k++;
            } else {
                $params[] = $k++;
            }
        }

        $this->table[$priority]["#^{$regexp}#i"] = array($defaults, $params);

        return $this;
    }

    public function route($URI)
    {
        return $this->match($URI);;
    }

}