<?php

/**
 * PHP File
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 A Tale Company
 *
 */

require_once __DIR__ . DS . 'default.php';

define('DEBUG_NONE', 0);
define('DEBUG_ALL', 15);
define('DEBUG_API', 1);
define('DEBUG_DATABASE', 2);
define('DEBUG_OAUTH', 4);
define('DEBUG_URL', 8);

define('DEVELOP', $CFG->develop);

define('PERMISSION_EXECUTE', 1);
define('PERMISSION_WRITE', 2);
define('PERMISSION_READ', 4);

define('SEED', $CFG->seed);
