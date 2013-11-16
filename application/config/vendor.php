<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (10/8/13)
 *
 * @copyright    La Cuarta Edad
 *
 */

$config = array(
    'controller' => ROOT . DS . 'application' . DS . 'controller',
    'model' => ROOT . DS . 'application' . DS . 'model',
    'view' => ROOT . DS . 'application' . DS . 'view'
);

/* DO NOT TOUCH UNDER THIS LINE */

define('APP_CONTROLLER', $config['controller']);
define('APP_MODEL', $config['model']);
define('APP_VIEW', $config['view']);