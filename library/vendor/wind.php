<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (05/05/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

require_once __DIR__ . '/autoload.php';
spl_autoload_register('Vendor\autoload');

if (!defined('WINDPHP_PATH')) {
    define('WINDPHP_PATH', __DIR__);
}

if (!defined('WINDPHP_CONTROLLER')) {
    define('WINDPHP_CONTROLLER', WINDPHP_PATH . '/controller');
}
if (!defined('WINDPHP_MODEL')) {
    define('WINDPHP_MODEL', WINDPHP_PATH . '/model');
}
if (!defined('WINDPHP_VIEW')) {
    define('WINDPHP_VIEW', WINDPHP_PATH . '/view');
}

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/api.oauth.php';

class API extends Vendor\API {}
abstract class AppController extends Vendor\MVC\Controller {}
class AppDispatcher extends Vendor\MVC\Dispatcher {}
class AppModel extends Vendor\MVC\Model {}
class AppRequest extends Vendor\MVC\Request {}
class AppResponse extends Vendor\MVC\Response {}
class AppRouter extends Vendor\MVC\Router {}
class AppView extends Vendor\MVC\View {}
class DataBase extends Vendor\DataBase {}
class DataSource extends Vendor\DataSource {}
class Encrypter extends Vendor\Encrypter {}
class OAuth extends Vendor\OAuth {}
class OAuthAPI extends Vendor\OAuthAPI {}
class Object extends Vendor\Object {}
class URL extends Vendor\URL {}
class XML extends Vendor\XML {}
