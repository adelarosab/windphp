<?php

/**
 *
 * @author      Adrian de la Rosa Bretin
 * @version     0.1
 *
 * @version     1.0 (05/20/2013)
 *              Rebuild consumer.
 *
 * @copyright   La Cuarta Edad
 *
 */

namespace Vendor\OAuth;

class Consumer extends Token
{

    private $callback;

    public function __construct($key, $secret, $callback = null)
    {
        parent::__construct($key, $secret);
        $this->callback = $callback;
    }

    public function callback($callback = null)
    {
        if (!isset($callback)) {
            return $this->callback;
        }

        $this->callback = $callback;

        return $this;
    }

}
