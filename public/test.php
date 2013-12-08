<?php

require_once
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

$definition = (new Vendor\MVC\Definition('name'))
    ['hello']
        ->type('VARCHAR')
    ['/hello']
    ['etc']
        ->type('HOLA')
    ['/etc'];

$definition->normalize();