<?php

/**
 *
 * @author    Adrian de la Rosa Bretin
 * @version   1.0 (11/03/2012)
 *
 * @copyright La Cuarta Edad
 *
 */

namespace Vendor;

abstract class OAuthAPI extends API
{

    public function __construct(OAuth $oauth)
    {
        $this->connector = $oauth;
    }

    public function token($key = null, $secret = null)
    {
        return $this->connector->token($key, $secret);
    }
}
