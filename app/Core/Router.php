<?php
namespace App\Core;

use App\Core\Request;
use App\Core\Response;

class Router {
    private Request $request;
    private array $routes = [];

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function get(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void {
        $cleanPath = '/' . trim($path, '/');
        // Convert route path with params (e.g. /vehicles/{id}) to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $cleanPath);
        
        if ($cleanPath === '/') {
            $regex = "#^/?$#i";
        } else {
            $regex = "#^" . rtrim($pattern, '/') . "/?$#i";
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $cleanPath,
            'pattern' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function resolve() {
        $method = $this->request->getMethod();
        $uri = '/' . trim($this->request->getUri(), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Filter named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware pipeline
                foreach ($route['middlewares'] as $mw) {
                    if (is_callable($mw)) {
                        $res = call_user_func($mw, $this->request);
                        if ($res === false) return;
                    } elseif (is_string($mw) && class_exists($mw)) {
                        $res = $mw::handle($this->request);
                        if ($res === false) return;
                    }
                }

                // Call handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    return call_user_func_array($handler, [$this->request, $params]);
                }

                if (is_array($handler)) {
                    list($controllerClass, $action) = $handler;
                    $controller = new $controllerClass();
                    return call_user_func_array([$controller, $action], [$this->request, $params]);
                }
            }
        }

        // Check if route exists with different method
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $uri)) {
                Response::json([
                    'success' => false,
                    'message' => 'Method Not Allowed',
                    'errors' => ['method' => "Method {$method} not allowed for {$uri}"]
                ], 405);
                return;
            }
        }

        // If it's an API request, return JSON 404
        if (str_starts_with($uri, '/api/')) {
            Response::json([
                'success' => false,
                'message' => 'API endpoint not found',
                'errors' => ['route' => "Endpoint not found: {$uri}"]
            ], 404);
            return;
        }

        // Web 404 fallback
        http_response_code(404);
        echo "404 Not Found - Rome & Ride";
        exit;
    }
}
