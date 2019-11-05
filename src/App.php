<?php

namespace E2;

class App
{
    /**
     * Parameters
     */
    private $routes;
    private $config;
    private $errors = [];
    private $blade;
    private $previousUrl;
    private $dotAccessConfig;

    private $sessionRedirect = 'e2_session_redirect';
    private $sessionErrors = 'e2_session_errors';
    private $sessionPrevious = 'e2_session_previous';
    
    /**
     *
     */
    public function __construct()
    {
        # Initialize Dotenv
        $dotenv = new \Dotenv\Dotenv(DOC_ROOT);
        $dotenv->load();
        $app = $this; # Define $app as $this because it's used in config.php
        $this->dotAccessConfig = new \Dflydev\DotAccessData\Data(include DOC_ROOT.'config.php');

        # Set timezone
        date_default_timezone_set($this->config('app.timezone'));

        # Extract and clear errors from session if they exist
        $this->errors = $this->getSession($this->sessionErrors);
        $this->setSession($this->sessionErrors, null);
        
        # Load routes
        $this->routes = include DOC_ROOT.'routes.php';

        # Initialize Blade
        $this->blade = new \Philo\Blade\Blade(DOC_ROOT . '/views', DOC_ROOT . '/cache');
    }

    /**
     * Returns a boolean value as to whether or not there are any validation errors
     */
    public function hasErrors()
    {
        return !is_null($this->errors) and count($this->errors) > 0;
    }
    
    /**
     * Returns validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Parses current url, returning a matching route if it exists
     */
    public function route()
    {
        $fullUrl = '/'.substr($_SERVER['REQUEST_URI'], 1);
        $parsedUrl = parse_url($fullUrl);
        $path = $parsedUrl['path'];

        # If route found...
        if (isset($this->routes[$path])) {
            # Persist previous URL; used for form validation redirection
            $this->previousUrl = $this->getSession($this->sessionPrevious);
            $this->setSession($this->sessionPrevious, $fullUrl);

            # Initialize Controller and invoke method
            $controllerName = "App\Controllers\\".$this->routes[$path][0];
            $controller = new $controllerName($this);
            $method = $this->routes[$path][1];
            return $controller->$method();
        # Route not found, return 404 error page
        } else {
            return $this->blade->view()->make('errors.404')->with(['app' => $this])->render();
        }
    }

    /**
     * Gets a "route parameter" (which is just a query string)
     */
    public function param($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Gets data from form submission; works with GET or POST
     */
    public function input($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } else {
            return $default;
        }
    }

    /**
     * Build a path relative to the document root
     */
    public function path($path)
    {
        return DOC_ROOT.$path;
    }

    /**
    * Returns a view; makes $app available in the view
    */
    public function view($view, $data = [])
    {
        echo $this->blade->view()->make($view)->with($data)->with(['app' => $this])->render();
    }

    /**
     * Redirect to a given path
     * Will optionally persist a set of data to the session
     * This data can be retrieved using `old`
     */
    public function redirect($path, $data = null)
    {
        if (!is_null($data)) {
            $this->setSession($this->sessionRedirect, $data);
        }

        header('Location: '.$path);
    }

    /**
     * Retrieve data from the session after a redirect
     */
    public function old($key, $default = null)
    {
        $retrieved = $this->getSession($this->sessionRedirect);

        $this->setSession($this->sessionRedirect, null);
        
        return $retrieved[$key];
    }

    /**
     * Validate an array of field names => rules
     */
    public function validate($rules)
    {
        $validator = new Validate($rules, $_POST);

        $errors = $validator->validate();
        
        # If there are errors...
        if (count($errors) > 0) {

            # Store the errors
            $this->setSession($this->sessionErrors, $errors);

            # Redirect to previous URL
            header('Location: '.$this->previousUrl);
            die();
        }
    }

    /**
     * Get a value from .env
     */
    public function env($name, $default = null)
    {
        return getenv($name) ?? $default;
    }

    /**
    * Get a value from the config
    */
    public function config($key, $default = null)
    {
        return $this->dotAccessConfig->get($key) ?? $default;
    }

    /**
     * Set a session value
     */
    private function setSession($key, $value)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    private function getSession($key, $default = null)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        return $_SESSION[$key] ?? $default;
    }
}
