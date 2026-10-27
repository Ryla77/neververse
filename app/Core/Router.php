<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, params:array<int,string>, handler:array{0:class-string,1:string}, middleware:array<int,string>}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];
                return '([^/]+)';
            },
            $pattern
        ) ?? $pattern;

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{0:array{0:class-string,1:string},1:array<string,string>,2:array<int,string>}
     */
    public function match(string $method, string $path): array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = rawurldecode((string) $matches[$index + 1]);
            }
            return [$route['handler'], $params, $route['middleware']];
        }
        throw new HttpException(404);
    }
}
