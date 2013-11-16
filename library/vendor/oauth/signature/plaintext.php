<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   0.1
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor\OAuth\Signature;

use Vendor\OAuth\Consumer;
use Vendor\OAuth\Request;
use Vendor\OAuth\Signature;
use Vendor\OAuth\Token;

class PLAINTEXT extends Signature
{

    public function __construct()
    {
        $this->name = 'PLAINTEXT';
    }

    public function build(Request $request, Consumer $consumer, Token $token)
    {
        $key = implode(
            '&',
            array_map(
                'rawurlencode',
                array($consumer->secret(), $token->secret())
            )
        );

        return $key;
    }
}
