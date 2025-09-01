<?php
declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var array<string, callable> */
    private array $getRoutes = [];
    /** @var array<string, callable> */
    private array $postRoutes = [];
    /** @var array<string, callable> */
    private array $patchRoutes = [];
    /** @var array<string, callable> */
    private array $putRoutes = [];
    /** @var array<string, callable> */
    private array $deleteRoutes = [];
    /** @var callable|null */
    private $fallbackHandler = null;

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    public function patch(string $path, callable $handler): void
    {
        $this->patchRoutes[$path] = $handler;
    }

    public function put(string $path, callable $handler): void
    {
        $this->putRoutes[$path] = $handler;
    }

    public function delete(string $path, callable $handler): void
    {
        $this->deleteRoutes[$path] = $handler;
    }

    public function fallback(callable $handler): void
    {
        $this->fallbackHandler = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($uri)) {
            $uri = '/';
        }

        if ($method === 'GET' && isset($this->getRoutes[$uri])) {
            ($this->getRoutes[$uri])();
            return;
        }

        if ($method === 'POST' && isset($this->postRoutes[$uri])) {
            ($this->postRoutes[$uri])();
            return;
        }

        if ($method === 'PUT' && isset($this->putRoutes[$uri])) {
            ($this->putRoutes[$uri])();
            return;
        }

        if ($method === 'DELETE' && isset($this->deleteRoutes[$uri])) {
            ($this->deleteRoutes[$uri])();
            return;
        }

        if ($method === 'PATCH' && isset($this->patchRoutes[$uri])) {
            ($this->patchRoutes[$uri])();
            return;
        }

        if ($this->fallbackHandler !== null) {
            ($this->fallbackHandler)();
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
    }
}


