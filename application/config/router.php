<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (10/10/13)
 *
 * @copyright    La Cuarta Edad
 *
 */

$router = array(
    '/' => array('controller' => 'main', 'action' => 'index')
);

/* DO NOT TOUCH UNDER THIS LINE */

require_once ROOT . 'library' . DS . 'vendor' . DS . 'autoload.php';

AppRouter::$default = $router;
