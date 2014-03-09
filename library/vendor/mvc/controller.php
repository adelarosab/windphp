<?php

/**
 * Definition controller class
 *
 * @author    Adrian de la Rosa Bretin <adrian.delarosab@gmail.com>
 * @copyright 2013 La Cuarta Edad
 *
 */

namespace Vendor\MVC;

use BadMethodCallException;
use DomainException;
use ErrorException;
use Exception;

class Controller
{

    const DEFAULT_ACTION = 'index';
    const PATH = APP_CONTROLLER;

    private $_rendered = false;
    private $_view;

    protected $action;
    protected $Request;
    protected $Response;

    public $helpers = [];

    public function __construct(Request &$request, Response &$response)
    {
        $this->Request = $request;
        $this->Response = $response;

        $this->bind(get_called_class());
        $this->Model = new Model(new Definition('Model'));

        if (!empty($this->helpers) && is_array($this->helpers)) {
            foreach ($this->helpers as $key => $value) {
                $fileName = strtolower(
                    APP_CONTROLLER . DS . 'helper' . DS . $value . '.php'
                );

                if (!file_exists($fileName) || !is_readable($fileName)) {
                    unset($this->helpers[$key]);
                    continue;
                }

                include_once $fileName;

                if (class_exists($value)) {
                    $this->helpers[$key] = new $value($request, $response);
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

    public function __invoke($action = self::DEFAULT_ACTION, $extension = '')
    {
        $this->action = (isset($action)) ? $action : self::DEFAULT_ACTION;
        $this->view($this->action);

        $this->call('beforeFilter');

        if ($this->isPrivate($this, $this->action)) {
            throw new BadMethodCallException('Controller not found');
        }

        $argv = array_slice(func_get_args(), 2);
        if (!empty($extension)) {
            $this->Response->type($extension);
        }
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

        include_once $file;

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

    protected final function bind($name, array $filter = array())
    {
        try {
            Model::load($name);
        } catch (Exception $e) {
            throw new ErrorException('Impossible to load model');
        }

        if (isset(Definition::$list[$name])) {
            $this->$name = new Model(Definition::$list[$name]);

            $dependencies = $this->$name->dependencies();
            foreach ($dependencies as $value) {
                if (!empty($filter) && !in_array($value, $filter)) {
                    continue;
                }

                $this->bind($value);
            }
        }
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
        if (isset($this->_view)) {
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

                    $this->forward(
                        $this->Request->here() . "/{$this->$className->ID}"
                    );
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
                    return $this->$className->find($ID);
                } else {
                    return $this->$className->find("all");
                }
                break;
        }

        return null;
    }

    public final function render()
    {
        if ($this->_rendered) {
            return;
        }

        $this->_rendered = !$this->_rendered;
        if (isset($this->_view)) {
            $this->Response->body($this->_view);
        }
    }

    public final function view($name = null)
    {
        if (!isset($name)) {
            return $this->_view;
        }

        $this->_view = View::fromClassName(array(get_called_class(), $name));

        return $this;
    }

}