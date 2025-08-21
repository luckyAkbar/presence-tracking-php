<?php
declare(strict_types=1);

namespace App\Container;

/**
 * Simple Dependency Injection Container
 * Manages object dependencies and their lifecycles
 */
final class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];
    
    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Bind a service to the container
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Bind a singleton service to the container
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function() use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        });
    }

    /**
     * Resolve a service from the container
     * 
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        throw new \InvalidArgumentException("Service [$abstract] not found in container");
    }

    /**
     * Check if service is bound
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }
}
