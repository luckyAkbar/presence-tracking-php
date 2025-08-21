<?php
declare(strict_types=1);

namespace App\Container;

use App\Config\Config;
use App\Http\Controllers\Auth0Controller;
use App\Security\EmailEncryption;
use App\Support\Db;
use App\User\UserRepository;
use App\User\UserService;

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

        // Controllers
        $container->bind(Auth0Controller::class, function(Container $container) {
            return new Auth0Controller($container->make(UserService::class));
        });
    }
}
