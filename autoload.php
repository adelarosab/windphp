<?php

/**
 *
 * @author      Adrián
 * @version     1.0 (02/21/2013)
 *
 * @copyright   La Cuarta Edad
 *
 * Archivo principal.
 *
 */

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

$packagePath = __DIR__ . DS . 'library';
$packages = array_slice(scandir($packagePath), 2);

foreach ($packages as $value) {
    include_once $packagePath . DS . $value . DS . 'autoload.php';
}

$configPath = __DIR__ . DS . 'application' . DS . 'config';
$configs = array_slice(scandir($configPath), 2);

foreach ($configs as $value) {
    include_once $configPath . DS . $value;
}
