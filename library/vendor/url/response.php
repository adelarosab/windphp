<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     2.0 (02/18/2012)
 *              Added URL: Final URL reached.
 *              Added information.
 * @version     2.1 (05/12/2013)
 *              ArrayAccess implemented.
 * @version     2.2 (06/04/2013)
 *              Request headers info added.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\URL;

use ArrayAccess;
use Vendor\URL;

class Response implements ArrayAccess
{

    public $code;
    public $body;
    public $headers;
    public $requestHeaders;
    public $url;

    public function __construct($raw, array $info)
    {
        $this->headers = array();
        $this->body = array();

        $pattern = "/^HTTP\\/1.[01] ([0-9]{3})/";
        $raw = explode("\r\n\r\n", $raw);

        foreach ($raw as $value) {
            if (preg_match($pattern, $value, $match)) {
                array_push($this->headers, $value);
            } else {
                array_push($this->body, $value);
            }
        }

        $this->url = $info['url'];
        $this->code = $info['http_code'];
        $this->requestHeaders = $info['request_header'];
        if (function_exists('http_parse_headers')) {
            $this->headers = array_map('http_parse_headers', $this->headers);
            $this->requestHeaders = http_parse_headers($this->requestHeaders);
        }
        $this->body = implode("\r\n\r\n", $this->body);
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        return;
    }

    public function offsetUnset($offset)
    {
        return;
    }
}