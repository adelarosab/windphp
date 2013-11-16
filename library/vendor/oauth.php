<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   2.0 (03/04/2012)
 *            Add callback method. Recover $_GET[oauth_token].
 *
 * @version   2.1 (02/16/2012)
 *            Constant version added. New input method of setSignature.
 *            Change setToken, now only receive a Object.
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor;

use Vendor\OAuth\Consumer;
use ErrorException;
use Vendor\OAuth\Signature\HMAC_SHA1;
use InvalidArgumentException;
use Vendor\OAuth\Signature\PLAINTEXT;
use Vendor\OAuth\Request;
use Vendor\OAuth\Signature;
use Vendor\OAuth\Token;

abstract class OAuth extends Request
{

    const HMAC_SHA1 = 1;
    const PLAINTEXT = 2;

    public static $DEBUG = DEBUG_NONE;

    protected $signature;

    public function __construct(
        $key,
        $secret,
        $callback = null,
        $signature = self::HMAC_SHA1
    ) {
        parent::__construct(
            new Consumer($key, $secret, $callback),
            new Token('', '')
        );
        $this->signature($signature);
    }

    public function access($url, array $data = array())
    {
        $method = self::POST;

        $response = $this->fetch($url, $method, $data);

        $data = array();
        parse_str($response->body, $data);

        if (!isset($data["oauth_token"])
            || !isset($data["oauth_token_secret"])
        ) {
            throw new ErrorException('OAuth: Access failed');
        }

        $this->token($data["oauth_token"], $data["oauth_token_secret"]);

        return $this->token();
    }

    public function authorize($url, array $data = array())
    {
        $default['oauth_token'] = $this
            ->token()
            ->key();
        $data = array_merge($default, $data);

        $url .= '?' . http_build_query($data);

        header('Location: ' . $url);
    }

    public function callback()
    {
        global $_GET;

        if (!isset($_GET['oauth_token'])) {
            throw new InvalidArgumentException(
                'Not such information at callback'
            );
        }

        $this->token($_GET['oauth_token']);

        return $this->token();
    }

    public function fetch(
        $url = null,
        $method = self::GET,
        array $data = array(),
        array $options = array()
    ) {
        $this->url((isset($url)) ? $url : $this->url());
        $this->method((isset($method)) ? $method : $this->method());
        if (!empty($data)) {
            $this->data($data);
        }
        $this->option($options);
        $this->sign($this->signature());

        if (self::$DEBUG & DEBUG_OAUTH) {
            print str_pad('', 75, '=') . PHP_EOL;
            print 'OAUTH' . PHP_EOL;
            print str_pad('', 75, '=') . PHP_EOL;
            print 'Token' . PHP_EOL;
            print"key = '{$this
                    ->token()
                    ->key()}' "
                . " secret = '{$this
                    ->token()
                    ->secret()}'" . PHP_EOL;
            print str_pad('', 75, '*') . PHP_EOL;
        }

        return parent::fetch();
    }

    public function initiate($url, array $data = array())
    {
        $this->token('', '');

        $method = self::POST;
        $default = array(
            'oauth_callback' => rawurlencode(
                $this
                    ->consumer()
                    ->callback()
            )
        );

        $data = array_merge($default, $data);

        $response = $this->fetch($url, $method, $data);

        $data = array();
        parse_str($response->body, $data);

        if (!isset($data["oauth_token"])
            || !isset($data["oauth_token_secret"])
        ) {
            throw new ErrorException('OAuth: Initiate failed');
        }

        $this->token($data["oauth_token"], $data["oauth_token_secret"]);

        return $this->token();
    }

    public function signature($signature = null)
    {
        if (!isset($signature)) {
            return $this->signature;
        }

        if ($signature instanceof Signature) {
            $this->signature = $signature;

            return $this;
        }

        if (is_string($signature)) {
            $signature = strtoupper($signature);
            if (defined("self::{$signature}")) {
                $signature = constant("self::{$signature}");
            } else {
                $signature = self::HMAC_SHA1;
            }
        }

        switch ($signature) {
            case self::PLAINTEXT:
                $this->signature = new PLAINTEXT();
                break;

            case self::HMAC_SHA1:
            default:
                $this->signature = new HMAC_SHA1();
                break;
        }

        return $this;
    }

    public function token($key = null, $secret = null)
    {
        return parent::token($key, $secret);
    }
}
