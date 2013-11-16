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

class HMAC_SHA1 extends Signature
{

    public function __construct()
    {
        $this->name = 'HMAC-SHA1';
    }

    public function build(Request $request, Consumer $consumer, Token $token)
    {
        $base = implode(
            '&',
            array_map(
                'rawurlencode',
                array(
                    $request->methodString(),
                    $request->url(),
                    http_build_query($request->data())
                )
            )
        );

        $key = implode(
            '&',
            array_map(
                'rawurlencode',
                array(
                    $consumer->secret(),
                    $token->secret()
                )
            )
        );

        return base64_encode(hash_hmac('sha1', $base, $key, true));
    }
}
