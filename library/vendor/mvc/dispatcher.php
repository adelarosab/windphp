<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/04/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use DomainException;
use Exception;

class Dispatcher
{

    protected function loadController($name)
    {
        Controller::load($name);
    }

    public function afterDispatch(Request &$request, Response &$response)
    {
    }

    public function beforeDispatch(Request &$request, Response &$response)
    {
    }

    public function dispatch(Request $request, Response $response)
    {
        $this->beforeDispatch($request, $response);

        $route = Router::getInstance()
            ->route($request->here());

        var_dump($route);

        try {
            $this->loadController($route['controller']);

            $controller = new $route['controller']($request, $response);
            call_user_func_array($controller, array_slice($route, 1));
        }
        catch (DomainException $e) {
            Model::load($route['controller']);

            if (isset(Definition::$list[$route['controller']])) {
                $controller = new Controller($request, $response);
                call_user_func_array($controller, array_slice($route, 1));
            } else {
                if (file_exists(APP_VIEW . '/error404.tpl')) {
                    $response->body(
                        file_get_contents(APP_VIEW . '/error404.tpl')
                    );
                }
                $response->statusCode(404);
            }
        }
        catch (Exception $e) {
            print $e->getMessage();

            if (file_exists(APP_VIEW . '/error500.tpl')) {
                $response->body(
                    file_get_contents(APP_VIEW . '/error500.tpl')
                );
            }
            $response->statusCode(500);
        }

        $this->afterDispatch($request, $response);

        return $response->send();
    }
}