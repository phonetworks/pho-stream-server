<?php

namespace Pho\Stream;

use DI\Container;
use FastRoute\Dispatcher;
use Pho\Stream\Exception\AppException;
use Pho\Stream\Exception\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class RequestHandler
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(Dispatcher $dispatcher)
    {
        $container = $this->container;

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $path = defined('PATH_INFO') ? PATH_INFO : ( $_SERVER['PATH_INFO'] ?? '/' );

        if ($httpMethod === 'OPTIONS') {
            return new EmptyResponse();
        }

        $routeInfo = $dispatcher->dispatch($httpMethod, $path);

        try {

            switch ($routeInfo[0]) {

                case Dispatcher::NOT_FOUND:
                    $response = $container->call(function (ServerRequestInterface $request, ResponseInterface $response) {
                        $response = new JsonResponse([
                            'message' => 'Not Found',
                        ], StatusCode::NOT_FOUND);
                        return $response;
                    });
                    break;

                case Dispatcher::METHOD_NOT_ALLOWED:
                    $response = $container->call(function (ServerRequestInterface $request, ResponseInterface $response) {
                        $response = new JsonResponse([
                            'message' => 'Method Not Allowed',
                        ], StatusCode::METHOD_NOT_ALLOWED);
                        return $response;
                    });
                    break;

                case Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];

                    if ($handler instanceof \Closure) {
                        $response = $container->call($handler, $vars);
                    }
                    elseif (is_string($handler)) {
                        list($className, $method) = explode('@', $handler);

                        $fullClassName = "Pho\\Stream\\Controller\\{$className}";
                        if (! class_exists($fullClassName)) {
                            throw new AppException("class {$fullClassName} does not exist");
                        }
                        if ($method !== null && ! method_exists($fullClassName, $method)) {
                            throw new AppException("method {$method} does not exist in class {$fullClassName}");
                        }

                        $controller = $container->get($fullClassName);

                        if (in_array($method, [ null, '' ])) {
                            if (! is_callable($controller)) {
                                throw new AppException("{$fullClassName} is not a callable");
                            }
                            $response = $container->call($controller, $vars);
                        }
                        else {
                            $response = $container->call([ $controller, $method ], $vars);
                        }
                    }
                    else {
                        throw new AppException("Unsupported handler type " . gettype($handler));
                    }
                    break;

                default:
                    throw new \UnexpectedValueException('Unexpected value of $routeInfo');
            }
        }
        catch (\Exception $ex) {
            $handler = $container->get(ExceptionHandler::class);
            $response = $container->call([ $handler, 'handle' ], [ $ex ]);
        }

        if (! $response instanceof ResponseInterface) {
            $response = new JsonResponse('', StatusCode::NO_CONTENT);
        }

        return $response;
    }
}
