<?php

namespace E2;

class App
{
    /**
     * Properties
     */
    private $routes;
    private $config;
    private $errors = [];
    private $old;
    private $blade;
    private $previousUrl;
    private $dotAccessConfig;
    private $db;

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
        $this->errors = $this->sessionGet($this->sessionErrors);
        $this->sessionSet($this->sessionErrors, null);

        # Extract and clear errors from session if they exist
        $this->old = $this->sessionGet($this->sessionRedirect);
        $this->sessionSet($this->sessionRedirect, null);

        # Load routes
        $this->routes = include DOC_ROOT.'routes.php';

        # Initialize Blade
        $this->blade = new \Philo\Blade\Blade(DOC_ROOT . '/views', DOC_ROOT . '/cache');
    }

    /**
     * Getter method for DB instance
     */
    public function db()
    {
        if (is_null($this->db)) {
            # Initialize Database PDO
            $host = $this->env('DB_HOST');
            $database = $this->env('DB_NAME');
            $username = $this->env('DB_USERNAME');
            $password = $this->env('DB_PASSWORD');
            $charset = $this->env('DB_CHARSET', 'utf8mb4');

            $this->db = new Database($host, $database, $usename, $password, 'utf8mb4');
        }
        return $this->db;
    }

    /**
     * Returns a boolean value as to whether or not there are any validation errors
     */
    public function errorsExist()
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
            $this->previousUrl = $this->sessionGet($this->sessionPrevious);
            $this->sessionSet($this->sessionPrevious, $fullUrl);

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
     * Gets *all* data (GET or POST) from form submission
     */
    public function inputAll()
    {
        $input = [];

        foreach ($_GET as $key => $value) {
            $input[$key] = $value;
        }

        foreach ($_POST as $key => $value) {
            $input[$key] = $value;
        }

        return $input;
    }

    /**
     * Build a path relative to the document root
     */
    public function path(string $path)
    {
        return DOC_ROOT.$path;
    }

    /**
    * Returns a view; makes $app available in the view
    */
    public function view(string $view, $data = [])
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
            $this->sessionSet($this->sessionRedirect, $data);
        }

        header('Location: '.$path);
    }

    /**
     * Retrieve data from the session after a redirect
     */
    public function old(string $key, $default = null)
    {
        return $this->old[$key] ?? $default;
    }

    /**
     * Validate an array of field names => rules
     */
    public function validate(array $rules)
    {
        $validator = new Validate($rules, $_POST);

        $errors = $validator->validate();
        
        # If there are errors...
        if (count($errors) > 0) {

            # Store the errors
            $this->sessionSet($this->sessionErrors, $errors);

            # Redirect to previous URL, persisting the input into the session
            # so it can be retrieved via `old` method
            $this->redirect($this->previousUrl, $this->inputAll());
            
            die();
        }
    }

    /**
     * Get a value from .env
     */
    public function env(string $name, $default = null)
    {
        # Note: getenv fill return `false`, not null, if a value does not exist
        return getenv($name) != false ? getenv($name) : $default;
    }

    /**
    * Get a value from the config
    */
    public function config(string $key, $default = null)
    {
        return $this->dotAccessConfig->get($key) ?? $default;
    }

    /**
     * Set a session value
     */
    public function sessionSet(string $key, $value)
    {
        $this->sessionStart();

        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public function sessionGet(string $key, $default = null)
    {
        $this->sessionStart();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Start the session if its not already started
     */
    private function sessionStart()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }
}
