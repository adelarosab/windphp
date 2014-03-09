<?php

/**
 * PHP File
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 A Tale Company
 *
 */
$router = array(
    '/' => array('controller' => 'main', 'action' => 'index')
);

/* DO NOT TOUCH UNDER THIS LINE */

require_once ROOT . 'library' . DS . 'vendor' . DS . 'autoload.php';

AppRouter::$default = $router;
