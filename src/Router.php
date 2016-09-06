<?php namespace DShumkov\Micra\Router;

use FastRoute\BadRouteException;

/**
 * Class Router
 * @uses \FastRoute\simpleDispatcher
 * @uses \FastRoute\RouteCollector
 * @uses \FastRoute\Dispatcher
 * @package DShumkov\Micra\Router
 */
class Router implements RouterInterface
{
    /**
     * @var array
     */
    protected $routes = [];
    /**
     * @var string
     */
    protected $prefix = '/';

    protected $errors = [];

    protected $container = null;

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function group($uri, \Closure $callback)
    {

        $uri = preg_replace('/^[\/]|[\/]$/', '', strtolower($uri));
        $this->prefix .= $uri . '/';
        call_user_func($callback, $this);
        $this->prefix = substr($this->prefix, 0, strlen($this->prefix) - strlen($uri));
    }

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function get($uri, $callback)
    {
        $this->addRoute('GET', $uri, $callback);
    }

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function post($uri, $callback)
    {
        $this->addRoute('POST', $uri, $callback);
    }

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function put($uri, $callback)
    {
        $this->addRoute('PUT', $uri, $callback);
    }

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function delete($uri, $callback)
    {
        $this->addRoute('DELETE', $uri, $callback);
    }

    /**
     * @param string $uri
     * @param \Closure|array $callback
     */
    public function any($uri, $callback)
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $uri, $callback);
    }

    /**
     * @param int $code
     * @param array|\Closure $callback
     */
    public function setError($code, $callback)
    {
        if ($code !== 404 AND $code !== 405) {
            throw new \BadMethodCallException('Bad parameter for method');
        }

        $this->errors[$code] = $callback;
    }


    /**
     * @throws BadRouteException
     */
    public function run()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $routeCollector) {
            foreach ($this->routes as $route) {
                $routeCollector->addRoute($route['method'], $route['uri'], $route['callback']);
            }
        });

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                if (array_key_exists(404, $this->errors)) {
                    $this->dispatcher($this->errors[404], null);
                    break;
                }
                throw new BadRouteException('Route not found.');
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                if (array_key_exists(405, $this->errors)) {
                    $this->dispatcher($this->errors[405], null);
                    break;
                }
                throw new BadRouteException('Method not allowed.');
            case \FastRoute\Dispatcher::FOUND:
                $this->dispatcher($routeInfo[1], $routeInfo[2]);
                break;
        }


    }

    /**
     * @param $method
     * @param $uri
     * @param $callback
     */
    protected function addRoute($method, $uri, $callback)
    {
        // Fix double slashes and wrap with brackets/add trail slash to url
        if (strlen($uri) > 1) {
            $uri = preg_replace(
                ['/([^\/|^\]]$)/', '/\/$/'],
                ['$1[/]', '[/]'],
                preg_replace('/\/+/', '/', $this->prefix . $uri)
            );
        }
        array_push($this->routes, [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback
        ]);
    }

    /**
     * @param $handler
     * @param $vars
     * @return mixed
     * @throws \BadFunctionCallException
     */
    protected function dispatcher($handler, $vars)
    {
        if (is_array($handler)) {
            return $this->controllerDispatcher($handler, $vars);
        }
        if (is_object($handler)) {
            if ('Closure' !== get_class($handler)) {
                throw new \BadFunctionCallException('Unacceptable route callback function type. Must be instance of Closure.');
            }

            return call_user_func($handler, $vars);
        }
        throw new \BadFunctionCallException('Unacceptable route callback type. Must be function or controller name');
    }

    /**
     * @param array $controller
     * @param $vars
     * @return mixed
     * @throws \BadRouteParameterException
     */
    protected function controllerDispatcher(array $controller, $vars)
    {
        if (null === $this->container) {
            throw new BadRouteParameterException('Can\'t use controller name as parameter for route cause container wasn\'t provided.');
        }
        if (isset($controller['controller'])) {
            if ($this->container->has($controller['controller'])) {
                return $this->container->call([$controller['controller'], $controller['method']], $vars);
            }
        }

        throw new BadRouteParameterException('Bad controller name pinned to route.');
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

}