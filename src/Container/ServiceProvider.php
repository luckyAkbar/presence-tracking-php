<?php
declare(strict_types=1);

namespace App\Container;

use App\Config\Config;
use App\Http\Controllers\Auth0Controller;
use App\Auth0\Auth0Service;
use App\Security\EmailEncryption;
use App\Support\Db;
use App\User\UserRepository;
use App\User\UserService;
use App\Http\Middlewares\AuthenticateUser;
use App\Http\Controllers\OrganizationController;
use App\Organization\OrganizationService;
use App\Organization\OrganizationsRepository;

/**
 * Service Provider - Configures dependency injection
 */
final class ServiceProvider
{
    public static function register(Container $container): void
    {
        // Database
        $container->bind(Db::class, function(Container $container) {
            return new Db();
        });

        // Security
        $container->bind(EmailEncryption::class, function(Container $container) {
            $masterKey = Config::getString('email_encryption_key');
            if (empty($masterKey)) {
                throw new \RuntimeException('EMAIL_ENCRYPTION_KEY environment variable is required');
            }
            return new EmailEncryption($masterKey);
        });

        // Repositories
        $container->bind(UserRepository::class, function(Container $container) {
            return new UserRepository(
                $container->make(Db::class),
                $container->make(EmailEncryption::class)
            );
        });

        // Services
        $container->bind(UserService::class, function(Container $container) {
            return new UserService($container->make(UserRepository::class));
        });

        $container->bind(OrganizationService::class, function(Container $container) {
            return new OrganizationService($container->make(OrganizationsRepository::class));
        });

        $container->bind(OrganizationsRepository::class, function(Container $container) {
            return new OrganizationsRepository($container->make(Db::class));
        });

        $container->bind(Auth0Service::class, function(Container $container) {
            return new Auth0Service();
        });

        // Controllers
        $container->bind(Auth0Controller::class, function(Container $container) {
            return new Auth0Controller($container->make(UserService::class));
        });

        $container->bind(OrganizationController::class, function(Container $container) {
            return new OrganizationController($container->make(OrganizationService::class));
        });

        // Middlewares
        $container->bind(AuthenticateUser::class, function(Container $container) {
            return new AuthenticateUser($container->make(Auth0Service::class), $container->make(UserRepository::class));
        });
    }
}
