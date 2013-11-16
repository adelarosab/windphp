<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   0.1
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor\OAuth;

use Vendor\OAuth\Consumer;
use Vendor\OAuth\Request;
use Vendor\OAuth\Token;

abstract class Signature
{

    protected $name;

    public function __toString()
    {
        return $this->name;
    }

    abstract public function build(
        Request $request,
        Consumer $consumer,
        Token $token
    );
}
