<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/06/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

class Request
{

    private $data = array();
    private $params = array();

    public function __construct($params = array())
    {
        $this->data = array_merge(apache_request_headers(), $_SERVER, $params);
        $this->params = array_merge($_COOKIE, $_GET, $_POST);
    }

    public function acceptLanguage($language = null)
    {
        if (!isset($language)) {
            return $this->data['HTTP_ACCEPT_LANGUAGE'];
        }

        $language = explode(',', $this->data['HTTP_ACCEPT_LANGUAGE']);

        // checking array if some value contains $language
        return array_reduce(
            array_map(
                function ($value) {
                    global $language;

                    return strpos($value, $language) !== false;
                },
                $language
            ),
            function ($v, $w) {
                return $v || $w;
            }
        );
    }

    public function browser()
    {
        return $this->data['HTTP_USER_AGENT'];
    }

    public function clientIP()
    {
        return $this->data['REMOTE_ADDR'];
    }

    public function data($key = null, $default = null)
    {
        if (!isset($key)) {
            return $this->params;
        }

        if (!array_key_exists($key, $this->params)) {
            return $default;
        }

        return $this->params[$key];
    }

    public function here()
    {
        return $this->data['REQUEST_URI'];
    }

    public function host()
    {
        return $this->data['SERVER_NAME'];
    }

    public function isAjax()
    {
        return
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public function method()
    {
        return $this->data['REQUEST_METHOD'];
    }

    public function redirect($URI)
    {
        if (substr($URI, 0, 1) != '/') {
            $URI = '/' . $URI;
        }

        header(
            'Location: ' . (($this->data('HTTPS')) ? 'https://' : 'http://')
            . $this->host() . $URI
        );
        die;
    }

    public function referer()
    {
        return $this->data['HTTP_REFERER'];
    }

}