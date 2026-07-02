<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, string $controller, string $action): self
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'action' => $action];
        return $this;
    }

    public function post(string $path, string $controller, string $action): self
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'action' => $action];
        return $this;
    }

    public function put(string $path, string $controller, string $action): self
    {
        $this->routes['PUT'][$path] = ['controller' => $controller, 'action' => $action];
        return $this;
    }

    public function delete(string $path, string $controller, string $action): self
    {
        $this->routes['DELETE'][$path] = ['controller' => $controller, 'action' => $action];
        return $this;
    }

    public function middleware(string $class, array $except = []): self
    {
        $this->middlewares[] = ['class' => $class, 'except' => $except];
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        $uri = $uri ?: '/';

        // Match route with parameters
        $routeFound = false;
        foreach ($this->routes[$method] ?? [] as $path => $handler) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $path);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                
                // Apply middlewares
                foreach ($this->middlewares as $middleware) {
                    if (!in_array($path, $middleware['except'])) {
                        $middlewareInstance = new $middleware['class']();
                        $middlewareInstance->handle();
                    }
                }

                $controllerName = 'App\\Controllers\\' . $handler['controller'];
                $controller = new $controllerName();
                $action = $handler['action'];
                $controller->$action(...$matches);
                $routeFound = true;
                break;
            }
        }

        if (!$routeFound) {
            http_response_code(404);
            View::render('errors/404');
        }
    }
}