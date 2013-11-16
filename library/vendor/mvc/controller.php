<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/02/2013)
 *
 * @version      2.0 (08/05/2013)
 *
 * @version      3.0 (10/13/2013)
 *               Helpers added.
 *
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use BadMethodCallException;
use DomainException;
use ErrorException;
use Exception;

abstract class Controller
{

    const DEFAULT_ACTION = 'index';
    const PATH = APP_CONTROLLER;

    private $rendered = false;
    private $view;

    protected $action;
    protected $Request;
    protected $Response;

    public $helpers = array();

    public function __construct(Request &$request, Response &$response)
    {
        $this->Request = $request;
        $this->Response = $response;

        $this->bind(get_called_class());
        $this->Model = new Model('Model');

        if (!empty($this->helpers) && is_array($this->helpers)) {
            foreach ($this->helpers as $key => $value) {
                $fileName = strtolower(
                    APP_VIEW . 'helper' . DS . $value . '.php'
                );

                if (!file_exists($fileName) || !is_readable($fileName)) {
                    unset($this->helpers[$key]);
                    continue;
                }

                require_once $fileName;

                if (class_exists($value)) {
                    $this->helpers[$key] = new $value;
                } else {
                    unset($this->helpers[$key]);
                }
            }
        }
    }

    public function __call($argv0, $argv)
    {
        array_unshift($argv, $argv0);

        return call_user_func_array(array($this, self::DEFAULT_ACTION), $argv);
    }

    public function __invoke($action = self::DEFAULT_ACTION)
    {
        $this->action = (isset($action)) ? $action : self::DEFAULT_ACTION;
        $this->view($this->action);

        $this->call('beforeFilter');

        if ($this->isPrivate($this, $this->action)) {
            throw new BadMethodCallException('Controller not found');
        }

        $argv = array_slice(func_get_args(), 1);
        $this->Response
            ->body(
                call_user_func_array(array($this, $this->action), $argv)
            );

        $this->call('beforeRender');
        $this->render();
        $this->call('afterFilter');
    }

    public static function load($className)
    {
        $file = strtolower(
            self::PATH . DIRECTORY_SEPARATOR . $className . '.php'
        );

        if (!file_exists($file)) {
            throw new DomainException('Controller not found');
        }

        require_once($file);

        if (!class_exists($className)
            && is_subclass_of($className, __CLASS__)
        ) {
            throw new DomainException('Controller not found');
        }
    }

    private function call($method)
    {
        $objects = array_merge(array($this), $this->helpers);
        $argv = array_slice(func_get_args(), 1);

        foreach ($objects as $value) {
            call_user_func_array(array($value, $method), $argv);
        }
    }

    private function isPrivate($object, $method)
    {
        $outClosure = function ($object, $method) {
            return !is_callable(array($object, $method));
        };

        return $outClosure($object, $method);
    }

    protected function forward($uri)
    {
        $dispatcher = new Dispatcher();

        $dispatcher->dispatch(
            new Request(array('REQUEST_URI' => $uri)),
            $this->Response
        );
    }

    protected function execute($uri)
    {
        $dispatcher = new Dispatcher();

        $response = $dispatcher->dispatch(
            new Request(array('REQUEST_URI' => $uri)),
            new Response()
        );

        return $response;
    }

    protected function set($key, $value)
    {
        if (isset($this->view)) {
            $this
                ->view()
                ->set($key, $value);
        }
    }

    public function afterFilter()
    {
    }

    public function beforeFilter()
    {
    }

    public function beforeRender()
    {
    }

    public final function bind($name, array $filter = array())
    {
        try {
            Model::load($name);
        }
        catch (Exception $e) {
            throw new ErrorException('Impossible to load model');
        }

        if (isset(Model::$list[$name])) {
            $this->$name = Model::$list[$name];

            $dependencies = $this->$name->dependencies(false);
            foreach ($dependencies as $value) {
                if (!empty($filter) && !in_array($value, $filter)) {
                    continue;
                }

                $this->bind($value);
            }
        }
    }

    public function index()
    {
        $className = get_called_class();
        $argv = func_get_args();
        $ID = (isset($argv[0])) ? $argv[0] : null;

        if (!isset($this->$className)) {
            return null;
        }

        switch ($this->Request->method()) {
        case 'POST':
            if (isset($ID)) {
                $this->Response->statusCode(404);
            } else {
                $this->$className
                    ->create($this->Request->data())
                    ->save();

                $this->Response->statusCode(201);

                return $this->$className->ID;
            }
            break;

        case 'PUT':
            if (isset($ID)) {
                $object = $this->$className
                    ->find($ID);

                if (isset($object)) {
                    $data = $this->Request->data();

                    // empty only supports reference
                    if (empty($data)) {
                        $this->Response->statusCode(204);
                    } else {
                        $this->$className
                            ->set($this->Request->data())
                            ->save();
                    }
                } else {
                    $this->Response->statusCode(404);
                }
            } else {
                $this->Response->statusCode(404);
            }
            break;

        case 'DELETE':
            if (isset($ID)) {
                $object = $this->$className
                    ->find($ID);

                if (isset($object)) {
                    $this->$className->delete($ID);
                } else {
                    $this->Response->statusCode(404);
                }
            } else {
                $this->Response->statusCode(404);
            }
            break;

        case 'GET':
        default:
            if (isset($ID)) {
                return json_encode($this->$className->find($ID));
            }
            break;
        }

        return null;
    }

    public final function render()
    {
        if ($this->rendered) {
            return;
        }

        $this->rendered = !$this->rendered;
        if (isset($this->view)) {
            $this->Response->body($this->view);
        }
    }

    public final function view($name = null)
    {
        if (!isset($name)) {
            return $this->view;
        }

        $this->view = View::fromClassName(array(get_called_class(), $name));

        return $this;
    }

}