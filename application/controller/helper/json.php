<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (11/10/13)
 *
 * @copyright    La Cuarta Edad
 *
 */


class JSON extends AppController
{

    public function beforeFilter()
    {
        $this->Response->type('json');
    }

    public function beforeRender()
    {
        $statusCode = $this->Response->statusCode();
        $body = $this->Response->body();
        $body = ($body !== null)
            ? ((json_decode($body)) ? json_decode($body) : $body)
            : null;

        $this->Response->statusCode(200);
        $this->Response->body(
            json_encode(
                array_filter(
                    array('status' => $statusCode, 'info' => $body)
                )
            )
        );
    }
} 