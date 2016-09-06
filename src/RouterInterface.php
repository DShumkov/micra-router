<?php namespace DShumkov\Micra\Router;

/**
 * Interface RouterInterface
 * @package DShumkov\Micra\Router
 */
interface RouterInterface
{

    /**
     * @param string $uri
     * @param \Closure $callback
     * @return
     */
    public function group($uri, \Closure $callback);

    /**
     * @param string $uri
     * @param \Closure|array $callback
     * @return void
     */
    public function get($uri, $callback);

    /**
     * @param string $uri
     * @param \Closure|array $callback
     * @return void
     */
    public function post($uri, $callback);

    /**
     * @param string $uri
     * @param \Closure|array $callback
     * @return void
     */
    public function put($uri, $callback);

    /**
     * @param string $uri
     * @param \Closure|array $callback
     * @return void
     */
    public function delete($uri, $callback);

    /**
     * @param string $uri
     * @param \Closure|array $callback
     * @return void
     */
    public function any($uri, $callback);

    /**
     * Set handler for 404 or 405 type of HTTP error.
     * @param int $code Must be only 404 or 405
     * @param \Closure|array $callback
     * @return void
     */
    public function setError($code, $callback);

    /**
     * Execute router
     * @return void
     */
    public function run();
}