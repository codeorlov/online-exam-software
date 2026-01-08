<?php
/**
 * Простий роутер для маршрутизації запитів
 * Підтримує GET, POST, PUT, DELETE методи
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Додати маршрут GET
     */
    public function get(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Додати маршрут POST
     */
    public function post(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Додати маршрут PUT
     */
    public function put(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Додати маршрут DELETE
     */
    public function delete(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Додати маршрут для будь-якого методу
     */
    public function any(string $path, string|callable $handler, array $middlewares = []): void
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $path, $handler, $middlewares);
    }

    /**
     * Додати маршрут
     */
    public function addRoute(string|array $methods, string $path, string|callable $handler, array $middlewares = []): void
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $httpMethod) {
            $this->routes[] = [
                'method' => strtoupper($httpMethod),
                'path' => $this->normalizePath($path),
                'handler' => $handler,
                'middlewares' => $middlewares
            ];
        }
    }

    /**
     * Нормалізувати шлях (прибрати зайві слеші)
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    /**
     * Обробити запит і знайти відповідний маршрут
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getUri();

        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $method && $this->matchRoute($route['path'], $uri, $params)) {
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    if (!$middlewareInstance->handle()) {
                        return;
                    }
                }

                $handler = $route['handler'];
                
                if (is_callable($handler)) {
                    call_user_func($handler, $params);
                } elseif (is_string($handler) && strpos($handler, '::') !== false) {
                    list($controllerClass, $method) = explode('::', $handler, 2);
                    $controller = new $controllerClass();
                    
                    if (method_exists($controller, $method)) {
                        $reflection = new \ReflectionMethod($controller, $method);
                        $methodParams = [];
                        
                        foreach ($reflection->getParameters() as $param) {
                            $paramName = $param->getName();
                            $paramType = $param->getType();
                            
                            if (isset($params[$paramName])) {
                                $value = $params[$paramName];
                                if ($paramType instanceof \ReflectionNamedType) {
                                    $typeName = $paramType->getName();
                                    if ($typeName === 'int') {
                                        $value = (int)$value;
                                    } elseif ($typeName === 'float') {
                                        $value = (float)$value;
                                    } elseif ($typeName === 'bool') {
                                        $value = (bool)$value;
                                    }
                                }
                                $methodParams[] = $value;
                            } elseif ($param->isDefaultValueAvailable()) {
                                $methodParams[] = $param->getDefaultValue();
                            } else {
                                $this->notFound();
                                return;
                            }
                        }
                        
                        call_user_func_array([$controller, $method], $methodParams);
                    } else {
                        $this->notFound();
                    }
                } else {
                    $this->notFound();
                }
                return;
            }
        }

        $this->notFound();
    }

    /**
     * Отримати URI з запиту
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        return $this->normalizePath($uri);
    }

    /**
     * Перевірити відповідність маршрута URI
     * Підтримує параметри виду :id
     */
    private function matchRoute(string $routePath, string $uri, array &$params): bool
    {
        $params = [];

        if ($routePath === $uri) {
            return true;
        }

        $routePattern = preg_replace('/:[a-zA-Z0-9_]+/', '([a-zA-Z0-9_-]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';

        if (preg_match($routePattern, $uri, $matches)) {
            array_shift($matches);

            preg_match_all('/:([a-zA-Z0-9_]+)/', $routePath, $paramNames);
            $paramNames = $paramNames[1];

            foreach ($paramNames as $index => $name) {
                if (isset($matches[$index])) {
                    $params[$name] = $matches[$index];
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Обробка 404 помилки
     */
    private function notFound(): void
    {
        http_response_code(404);
        $view = new View();
        $view->render('errors/404');
        exit;
    }
}
