<?php

require_once
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

$object = new Object();
$object->hello('world');

debug();

var_dump($object);

array_walk(
    $object,
    function ($value, $key) {
        var_dump($value);
        print $value;
    }
);