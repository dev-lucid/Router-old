<?php

namespace DevLucid;

class Router
{
    private static $instance;

    public function __construct()
    {
        $this->controller_path = null;
        $this->view_path       = null;
        $this->mode            = 'parameter';
        $this->parameter_name  = 'todo';
        $this->logger          = null;
    }

    public static function get_instance($config=[])
    {
        if (null !== static::$instance) {
             return static::$instance;
        }
        static::$instance = new Router();
        foreach ($config as $key=>$value)
        {
            static::$instance->$key = $value;
        }

        return static::$instance;
    }

    public static function controller($name)
    {
        $router = Router::get_instance();
        $controller_path  = $router->controller_path.$name.'.php';
        $controller_class = $name .'_controller';

        if (is_object($router->logger))
        {
            $this->logger->debug('Loading controller class '.$controller_class.' from file '.$controller_path);
        }

        if (!file_exists($controller_path))
        {
            if (is_object($router->logger))
            {
                $router->logger->error('Could not locate controller file. Expected to find '.$controller_path);
            }
            throw new \Exception('Could not locate controller file. Expected to find '.$controller_path);
        }
        include($controller_path);

        if (!class_exists($controller_class))
        {
            if (is_object($router->logger))
            {
                $router->logger->error('Could not locate controller class in file '.$controller_path.'. Expected to find '.$controller_class);
            }
            throw new \Exception('Could not locate controller class in file '.$controller_path.'. Expected to find '.$controller_class);
        }
        $controller = new $controller_class();

        return $controller;
    }

    public static function view($name)
    {
        $router = Router::get_instance();
        $view_path = $router->view_path.$name.'.php';

        if (is_object($router->logger))
        {
            $router->logger->debug('Loading view '.$view_path);
        }

        if (!file_exists($view_path))
        {
            if (is_object($router->logger))
            {
                $router->logger->error('Could not locate view file. Expected to find '.$view_path);
            }
            throw new \Exception('Could not locate view file. Expected to find '.$view_path);
        }

        include($view_path);
    }

    public function process()
    {
        if (isset($_REQUEST[$this->parameter_name]))
        {
            $parts = explode('/',$_REQUEST[$this->parameter_name]);
            
            if (count($parts) !== 2)
            {
                throw new \Exception('Invalid format for router parameter. The format should be [controller]/[Method name], or view/[view name]. ');
            }

            if ($parts[0] == 'view')
            {
                \DevLucid\Router::view($parts[1]);
            }
            else
            {
                $controller = \DevLucid\Router::controller($parts[0]);
                $method_name = $parts[1];
                $controller->$method_name();
            }
        }
        else
        {
            $router = Router::get_instance();
            if (is_object($router->logger))
            {
                $router->logger->error('Could not find valid routing parameter. Expected to find parameter named '.$this->parameter_name.' in $_REQUEST');
            }
            throw new \Exception('Could not find valid routing parameter. Expected to find parameter named '.$this->parameter_name.' in $_REQUEST');
        }
    }
}
