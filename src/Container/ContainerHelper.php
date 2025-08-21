<?php
declare(strict_types=1);

namespace App\Container;

use App\Http\Controllers\Auth0Controller;
use App\User\UserRepositoryInterface;
use App\User\UserServiceInterface;

/**
 * Container Helper - Provides IDE-friendly container access
 * This class provides typed methods for container resolution
 */
final class ContainerHelper
{
    public function __construct(
        private Container $container
    ) {}

    /**
     * Get AuthController instance
     */
    public function auth0Controller(): Auth0Controller
    {
        return $this->container->make(Auth0Controller::class);
    }

    /**
     * Get any service by class name (fallback method)
     * 
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }
}
