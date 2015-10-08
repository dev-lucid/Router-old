# Router
A simple PHP router with minimal configuration. It does not use a regular expression, and maps parameters in the url directly to a file name and class name. This code does not care what occurs once these controllers or views are loaded.

Example code:

```php
$router = DevLucid\Router::get_instance([
    'parameter_name' => 'todo',  # this is the default value
    'controller_path' => __DIR__.'controllers/', # this is an example, no default value
    'view_path' => __DIR__.'views/',       # this is an example, no default value
]);
$router->process();
```

In this code, the call to ->process() will look at $_REQUEST['todo'] for an action to perform. There are two patterns recognized: one for views, and one for controllers. 

### Loading views

Given the above code, the following querystring would load a view named testview.php: ?todo=view/testview 


### Loading controllers

Given the above code, the following querystring would include a file named users.php, create an instance of a class named 'users_controller', and call a method named authenticate: ?todo=users/authenticate 


## Installation
The easiest way to install is using composer: 

```bash
composer require devlucid/router
```

## Rules for routes
* if a view's filename starts with an underscore (ex: _my_view.php), then it will not be loaded via ->process(). An exception will be thrown in this case. Such a view may still be loaded from a different view or controller. 

* if the class name for a controller starts with an underscore (ex: _my_test_controller), then it will not be loaded via ->process(). An exception will be thrown in this case. Such a controller may still be loaded from a different view or controller. 

* The path for a view or controller may not move up in the filesystem hierarchy using ../. 


## Additional configuration options

### Using URL rewriting via mod_rewrite

In the default configuration, the router looks for a URL parameter named 'todo' to determine which controller or view to load. By using url rewriting, you can 

```apache
RewriteEngine On
RewriteBase /
 
# Disable rewrite for valid directory/files     
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d 
RewriteRule ^(.*)$ index.php?todo=$1 [L,QSA,B]
```

Notably this example assumes that the router entry point is index.php, and that / is the base. 


### Using URL rewriting in nginx

I haven't tested this at all. Try something like this:

```nginx
server {
    ...
    rewrite ^(.*)$ index.php?todo=$1 [L,QSA,B]
    ...
}
```

### Changing parameter name

```php
$router = DevLucid\Router::get_instance([
    'parameter_name' => 'route',  # this is a custom value
    'controller_path' => __DIR__.'controllers/', # this is an example, no default value
    'view_path' => __DIR__.'views/',       # this is an example, no default value
]);
$router->process();
```

Given this configuration, the router will look for $\_REQUEST['route'] instead of $_REQUEST['todo']

### Using a PSR-3 compliant logger:

In this example, assume that $logger contains an instantiated PSR-3-compliant logger. 

```php
global $logger;
$router = DevLucid\Router::get_instance([
    'parameter_name' => 'todo',  # this is the default value
    'controller_path' => __DIR__.'controllers/', # this is an example, no default value
    'view_path' => __DIR__.'views/',       # this is an example, no default value
    'logger' => $logger,
]);
$router->process();
```

Whenever a view is loaded, you should see a message like this in your debug log:

```log
Loading view /var/www/dev/app/views/_nav1_logged_in.php
```

Whenever a controller is loaded, you should see a message like this in your debug log:
