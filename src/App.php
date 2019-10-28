<?php

namespace E2;

class App
{
    private $routes;

    public $config;

    private $blade;

    public $dotAccessConfig;
    
    /**
     *
     */
    public function __construct()
    {
        # Setup Dotenv
        $dotenv = new \Dotenv\Dotenv(DOC_ROOT);
        $dotenv->load();

        $app = $this;

        $this->dotAccessConfig = new \Dflydev\DotAccessData\Data(include DOC_ROOT.'config.php');

        date_default_timezone_set($this->config('app.timezone'));

        $this->routes = include DOC_ROOT.'routes.php';

        $this->blade = new \Philo\Blade\Blade(DOC_ROOT . '/views', DOC_ROOT . '/cache');
    }

    /**
     *
     */
    public function config($key)
    {
        return $this->dotAccessConfig->get($key);
    }


    /**
     *
     */
    public function route()
    {
        # Get the path
        $uri = '/'.substr($_SERVER['REQUEST_URI'], 1);

        if (isset($this->routes[$uri])) {
            $controllerName = "App\Controllers\\".$this->routes[$uri][0];
            $controller = new $controllerName($this);
            $method = $this->routes[$uri][1];
            return $controller->$method();
        } else {
            return $this->blade->view()->make('errors.404')->with(['app' => $this])->render();
        }
    }

    /**
    *
    */
    public function view($view, $data = [])
    {
        echo $this->blade->view()->make($view)->with($data)->with(['app' => $this])->render();
    }

    /**
     *
     */
    public function env($name, $default)
    {
        return getenv($name) ?? $default;
    }
}
