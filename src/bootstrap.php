<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Load configuration from environment variables
App\Config\Config::init([
    'db_host' => getenv('DB_HOST') ?: 'mysql',
    'db_port' => (int)(getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'presence',
    'db_user' => getenv('DB_USER') ?: 'presence',
    'db_pass' => getenv('DB_PASSWORD') ?: 'presence_pass',
]);