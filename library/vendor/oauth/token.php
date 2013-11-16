<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     1.0
 *
 * @version     2.0 (05/20/2013)
 *              Rebuild token.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\OAuth;

use Serializable;

class Token implements Serializable
{

    private $key;
    private $secret;

    public function __construct($key, $secret)
    {
        $this->key($key);
        $this->secret($secret);
    }

    public function key($key = null)
    {
        if (!isset($key)) {
            return $this->key;
        }

        $this->key = $key;

        return $this;
    }

    public function secret($secret = null)
    {
        if (!isset($secret)) {
            return $this->secret;
        }

        $this->secret = $secret;

        return $this;
    }

    public function serialize()
    {
        return serialize(array($this->key(), $this->secret()));
    }

    public function unserialize($serialized)
    {
        $token = unserialize($serialized);
        $this->key($token[0]);
        $this->secret($token[1]);
    }
}
