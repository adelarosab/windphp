<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     2.0 (02/18/2012)
 *              Method build added: Return an array with request configuration.
 *
 * @version     3.0 (05/20/2013)
 *              Rebuild request.
 * @version     3.0.1 (06/04/2013)
 *              Minor bugs setDataArray.
 * @version     3.1 (07/09/2013)
 *              Removed buildQuery (mistaken needed).
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\URL;

use ErrorException;
use InvalidArgumentException;
use Vendor\URL;
use UnderflowException;

class Request
{

    const CONNECT = 1;
    const DELETE = 2;
    const GET = 4;
    const HEAD = 8;
    const OPTIONS = 16;
    const POST = 32;
    const PUT = 64;
    const TRACE = 128;

    private $data = array();
    private $method = self::GET;
    private $options = array();
    private $url;

    public function __construct()
    {
        $this->reset();
    }

    public function __call($argv0, $argv)
    {
        $this->data($argv0, $argv[0]);

        return $this;
    }

    private function buildURL(array $url)
    {
        $url = array_filter($url);
        $output = '';

        if (isset($url['scheme'])) {
            $output .= "{$url['scheme']}://";
        }
        if (isset($url['user'])) {
            if (isset($url['pass'])) {
                $url['user'] .= ":{$url['pass']}";
            }
            $output .= "{$url['user']}@";
        }
        if (isset($url['host'])) {
            $output .= $url['host'];
        }
        if (isset($url['port'])) {
            $output .= ":{$url['port']}";
        }
        if (isset($url['path'])) {
            $output .= $url['path'];
        }
        if (isset($url['query'])) {
            $output .= "?{$url['query']}";
        }

        return $output;
    }

    protected function build()
    {
        if (empty($this->url)) {
            throw new InvalidArgumentException(
                "CURL: Impossible to reach {$this->url()}"
            );
        }

        $options = array();
        switch ($this->method) {
            case self::HEAD:
            case self::OPTIONS:
            case self::GET:
            case self::PUT:
                $url = parse_url($this->url());
                $url['query'] = http_build_query($this->data());
                array_filter($url);

                $options[CURLOPT_URL] = $this->buildURL($url);
                break;

            case self::POST:
            default:
                $options[CURLOPT_URL] = $this->url();
                $options[CURLOPT_POSTFIELDS] = $this->data();
                break;
        }

        $this->option($options);

        return $this->options;
    }

    protected function reset()
    {
        $this->data = array();
        $this->method(self::GET);
        $this->options = array(
            CURLINFO_FILETIME => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60
        );
    }

    public function data($name = null, $value = null)
    {
        if (!isset($name)) {
            return $this->data;
        }

        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    public function file($fileName, $progressFunction = null)
    {
        if (!file_exists($fileName)) {
            throw new ErrorException("File not found: {$fileName}");
        }

        $this->method(self::PUT);
        $this->option(
            array(
                CURLOPT_INFILE => $fileName,
                CURLOPT_INFILESIZE => filesize($fileName),
                CURLOPT_UPLOAD => true
            )
        );

        if (isset($progressFunction)) {
            $this->option(CURLOPT_PROGRESSFUNCTION, $progressFunction);
        }

        return $this;
    }

    public function method($method = null)
    {
        if (!isset($method)) {
            return $this->method;
        }

        if (is_string($method)) {
            $method = strtoupper($method);
            if (defined("self::{$method}")) {
                $method = constant("self::{$method}");
            } else {
                $method = self::GET;
            }
        }

        $this->method = $method;

        $options = array();
        switch ($this->method) {
            case self::HEAD:
                $options[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                continue;

            case self::OPTIONS:
                $options[CURLOPT_CUSTOMREQUEST] = 'OPTIONS';
                continue;

            case self::POST:
                $options[CURLOPT_CUSTOMREQUEST] = 'POST';
                break;

            case self::PUT:
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;

            case self::CONNECT:
                $options[CURLOPT_CUSTOMREQUEST] = 'CONNECT';
                break;

            case self::DELETE:
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;

            case self::TRACE:
                $options[CURLOPT_CUSTOMREQUEST] = 'TRACE';
                break;

            default:
                $options[CURLOPT_HTTPGET] = true;
                break;
        }

        $this->option($options);

        return $this;
    }

    public function noCache()
    {
        $this->option(
            array(
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_FRESH_CONNECT => true
            )
        );

        return $this;
    }

    public function option($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $rkey => $rvalue) {
                $this->options[$rkey] = $rvalue;
            }
        } else {
            $this->options[$key] = $value;
        }

        return $this;
    }

    public function resumeOn($offset)
    {
        if (!isset($this->options[CURLOPT_INFILE])) {
            throw new UnderflowException('CURL: Not such file or directory');
        }

        $this->option(CURLOPT_RESUME_FROM, $offset);
    }

    public function url($url = null)
    {
        if (!isset($url)) {
            return $this->url;
        }

        $url = parse_url($url);

        if (isset($url['query'])) {
            $data = array();
            parse_str($url['query'], $data);

            $this->data($data);
            unset($url['query']);
        }

        $this->url = $this->buildURL($url);

        return $this;
    }
}
