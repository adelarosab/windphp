<?php

/**
 * PHP Class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 A Tale Company
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
