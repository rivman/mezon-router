<?php
namespace Mezon\Router;

// TODO add hash for static routes
// TODO add custom types
// TODO PSR-7 compliant
// TODO add non-static routes optimizations like here https://medium.com/@nicolas.grekas/making-symfonys-router-77-7x-faster-1-2-958e3754f0e1
// TODO add 404 test benchmark

/**
 * Class Router
 *
 * @package Mezon
 * @subpackage Router
 * @author Dodonov A.A.
 * @version v.1.0 (2019/08/15)
 * @copyright Copyright (c) 2019, aeon.org
 */

/**
 * Router class
 */
class Router
{

    use \Mezon\Router\RoutesSet;

    use \Mezon\Router\UrlParser;

    /**
     * Method wich handles invalid route error
     *
     * @var callable
     */
    private $invalidRouteErrorHandler;

    /**
     * Method returns request method
     *
     * @return string Request method
     */
    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $_SERVER['REQUEST_METHOD'] = $this->getRequestMethod();

        $this->invalidRouteErrorHandler = [
            $this,
            'noProcessorFoundErrorHandler'
        ];

        $this->initDefaultTypes();
    }

    /**
     * Method fetches actions from the objects and creates GetRoutes for them
     *
     * @param object $object
     *            Object to be processed
     */
    public function fetchActions(object $object): void
    {
        $methods = get_class_methods($object);

        foreach ($methods as $method) {
            if (strpos($method, 'action') === 0) {
                $route = \Mezon\Router\Utils::convertMethodNameToRoute($method);
                $this->addGetRoute($route, $object, $method);
                $this->addPostRoute($route, $object, $method);
            }
        }
    }

    /**
     * Method adds route and it's handler
     *
     * $callback function may have two parameters - $route and $parameters. Where $route is a called route,
     * and $parameters is associative array (parameter name => parameter value) with URL parameters
     *
     * @param string $route
     *            Route
     * @param mixed $callback
     *            Collback wich will be processing route call.
     * @param string|array $requestMethod
     *            Request type
     */
    public function addRoute(string $route, $callback, $requestMethod = 'GET'): void
    {
        $route = '/' . trim($route, '/') . '/';

        if (is_array($requestMethod)) {
            foreach ($requestMethod as $r) {
                $this->addRoute($route, $callback, $r);
            }
        } else {
            $routes = &$this->getRoutesForMethod($requestMethod);
            // this 'if' is for backward compatibility
            // remove it on 02-04-2021
            if (is_array($callback) && isset($callback[1]) && is_array($callback[1])) {
                $callback = $callback[1];
            }
            $routes[$route] = $callback;
        }
    }

    /**
     * Method processes no processor found error
     *
     * @param string $route
     *            Route
     */
    public function noProcessorFoundErrorHandler(string $route)
    {
        throw (new \Exception(
            'The processor was not found for the route ' . $route . ' in ' . $this->getAllRoutesTrace()));
    }

    /**
     * Method sets InvalidRouteErrorHandler function
     *
     * @param callable $function
     *            Error handler
     */
    public function setNoProcessorFoundErrorHandler(callable $function)
    {
        $oldErrorHandler = $this->invalidRouteErrorHandler;

        $this->invalidRouteErrorHandler = $function;

        return $oldErrorHandler;
    }

    /**
     * Processing specified router
     *
     * @param mixed $route
     *            Route
     */
    public function callRoute($route)
    {
        $route = \Mezon\Router\Utils::prepareRoute($route);
        $requestMethod = $this->getRequestMethod();

        if (($result = $this->findStaticRouteProcessor($this->getRoutesForMethod($requestMethod), $route)) !== false) {
            return $result;
        }

        if (($result = $this->findDynamicRouteProcessor($this->getRoutesForMethod($requestMethod), $route)) !== false) {
            return $result;
        }

        call_user_func($this->invalidRouteErrorHandler, $route);
    }
}
