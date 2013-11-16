<?php

/**
 *
 * @author      Adrián
 * @version     1.0 (02/21/2013)
 * @version     1.1 (03/23/2013)
 * @version     1.2 (04/07/2013)
 *
 * @copyright   La Cuarta Edad
 *
 * Archivo principal.
 *
 */

chdir(dirname(__DIR__));

require_once 'autoload.php';

session_start();

$dispatcher = new AppDispatcher();
echo $dispatcher->dispatch(new AppRequest(), new AppResponse());