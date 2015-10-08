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
        $this->default_todo    = 'view/index';
        $this->logger          = null;
    }

    private function throw_exception($msg)
    {
        if (is_object($this->logger))
        {
            $this->logger->error($msg);
        }
        throw new \Exception($msg);
    }

    private function log_debug($msg)
    {
        if (is_object($this->logger))
        {
            $this->logger->debug($msg);
        }
    }

    public static function get_instance($config=[])
    {
        if (null !== static::$instance) {
             return static::$instance;
        }
        static::$instance = new Router();
        foreach ($config as $key=>$value)
        {
            if ($key === 'view_path' or $key === 'controller_path')
            {
                static::$instance->$key = realpath($value);
            }
            else
            {
                static::$instance->$key = $value;
            }
            
        }

        return static::$instance;
    }

    public static function check_path_sanity($base_path, $file_path)
    {
        if(strpos($file_path, $base_path) !== 0 || strpos($file_path, $base_path) === false)
        { 
            Router::get_instance()->throw_exception('Invalid path found. A view or controller path must not move up in the filesystem hierarchy. Tried to load '.$file_path.' from base path '.$base_path); 
        }
    }

    public static function controller($name,$allow_underscore_initial = true)
    {
        $router = Router::get_instance();
        
        $controller_class ='DevLucid\\'.(str_replace('/','_',$name)) .'_controller';
        if ($allow_underscore_initial === false)
        {
            if (strpos($controller_class,'_') === 0)
            {
                $router->throw_exception('A controller cannot be called via url if its class name starts with an underscore. It may still be called from another controller method or view.');
            }
        }

        # only bother loading the controller class if it doesn't exist
        if (!class_exists($controller_class))
        {
            $controller_path  = realpath($router->controller_path .'/'. $name . '.php');
            if (is_null($controller_path) or $controller_path === '')
            {
                $router->throw_exception('Invalid path found. Could not map controller '.$name.' to actual file');
            }

            Router::check_path_sanity($router->controller_path, $controller_path);
            
            $router->log_debug('Loading controller class '.$controller_class.' from file '.$controller_path);

            if (!file_exists($controller_path))
            {
                $router->throw_exception('Could not locate controller file. Expected to find '.$controller_path);
            }
            include($controller_path);

            if (!class_exists($controller_class))
            {
                $router->throw_exception('Could not locate controller class in file '.$controller_path.'. Expected to find '.$controller_class);
            }
        }
        $controller = new $controller_class();

        return $controller;
    }

    public static function view($name,$allow_underscore_initial = true)
    {
        $router = Router::get_instance();

        $view_path = realpath($router->view_path . '/'.$name . '.php');
        if (is_null($view_path) or $view_path === '')
        {
            $router->throw_exception('Invalid path found. Could not map view '.$name.' to actual file');
        }
        Router::check_path_sanity($router->view_path, $view_path);
        
        if ($allow_underscore_initial === false)
        {
            $file_name = basename($view_path);
            if (strpos($file_name,'_') === 0)
            {
                $router->throw_exception('A view file cannot be called via url if its name starts with an underscore. It may still be called from another view that does not start with an underscore, or from a controller method.');
            }
        }

        $router->log_debug('Loading view '.$view_path);

        if (!file_exists($view_path))
        {
            $router->throw_exception('Could not locate view file. Expected to find '.$view_path);
        }

        return include($view_path);
    }


    public function process()
    {
        if (!isset($_REQUEST[$this->parameter_name]))
        {
            $_REQUEST[$this->parameter_name] = $this->default_todo;
        }
        $parts = explode('/',$_REQUEST[$this->parameter_name]);
        
        if ($parts[0] == 'view')
        {
            array_shift($parts);
            \DevLucid\Router::view(implode('/',$parts),false);
        }
        else
        {
            $method_name = array_pop($parts);
            if (strpos($method_name,'_') === 0)
            {
                $router->throw_exception('A controller method cannot be called via url if its name starts with an underscore. It may still be called from another controller method or view that does not start with an underscore.');
            }
            $controller = \DevLucid\Router::controller(implode('/',$parts),false);
            
            $controller->$method_name();
        }
    }
}
