<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (09/14/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor {

    function autoload($className)
    {
        $className = ltrim($className, '\\');
        $prefix = __NAMESPACE__ . '\\';
        $owned = substr_compare($className, $prefix, 0, strlen($prefix)) !== 0;

        if ($owned) {
            return;
        }

        $className = substr($className, strlen(__NAMESPACE__) + 1);
        $fileName = '';

        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace)
                . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        require_once __DIR__ . DIRECTORY_SEPARATOR . $fileName;
    }

    spl_autoload_register('Vendor\autoload');

    require_once __DIR__ . '/function.php';
    require_once __DIR__ . '/api.oauth.php';

}

namespace {

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

}
