<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   1.0
 * @version   2.0 (02/16/2012)
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor\OAuth;

use Vendor\OAuth;
use ReflectionClass;
use Vendor\URL;

class Request extends URL
{

    const VERSION = '1.0';

    private $consumer;
    private $token;

    public function __construct(Consumer $consumer, Token $token)
    {
        $this->consumer = $consumer;
        $this->token = $token;
        parent::__construct();
    }

    protected function consumer($key = null, $secret = null)
    {
        if (!isset($key)) {
            return $this->consumer;
        }

        $this->consumer = ($key instanceof Consumer)
            ? $key
            : new Consumer($key, $secret);

        return $this;
    }

    protected function reset()
    {
        parent::reset();

        $this->data(
            array(
                'oauth_version' => self::VERSION,
                'oauth_nonce' => md5(microtime() . mt_rand()),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $this
                        ->consumer()
                        ->key(),
                'oauth_token' => $this
                        ->token()
                        ->key()
            )
        );
    }

    protected function sign(Signature $signature)
    {
        $this->data('oauth_signature_method', (string)$signature);
        // on purpose, it able read method in build method of signature
        $this->data(
            'oauth_signature',
            $signature->build($this, $this->consumer(), $this->token())
        );
    }

    protected function token($key = null, $secret = null)
    {
        if (!isset($key)) {
            return $this->token;
        }

        $this->token = ($key instanceof Token) ? $key
            : new Token($key, $secret);
        $this->data(
            'oauth_token',
            $this
                ->token()
                ->key()
        );

        return $this;
    }

    public function data($name = null, $value = null)
    {
        if (!isset($name)) {
            $data = parent::data($name, $value);
            ksort($data);
            $data = array_filter(
                $data,
                function ($value) {
                    return $value !== null;
                }
            );

            return $data;
        }

        return parent::data($name, $value);
    }

    public function methodString()
    {
        $reflection = new ReflectionClass($this);
        $constants = $reflection->getConstants();

        return array_search($this->method(), $constants);
    }
}
