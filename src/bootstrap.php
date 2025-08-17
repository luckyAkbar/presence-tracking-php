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
    'app_env' => getenv('APP_ENV') ?: 'development',
    'app_base_url' => getenv('APP_BASE_URL') ?: 'http://127.0.0.1:8080',

    'db_host' => getenv('DB_HOST') ?: 'mysql',
    'db_port' => (int)(getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'presence',
    'db_user' => getenv('DB_USER') ?: 'presence',
    'db_pass' => getenv('DB_PASSWORD') ?: 'presence_pass',
    // Auth0
    'auth0_domain' => getenv('AUTH0_DOMAIN') ?: '',
    'auth0_client_id' => getenv('AUTH0_CLIENT_ID') ?: '',
    'auth0_client_secret' => getenv('AUTH0_CLIENT_SECRET') ?: '',
    'auth0_cookie_secret' => getenv('AUTH0_COOKIE_SECRET') ?: '',
    'auth0_redirect_uri' => getenv('APP_BASE_URL') . getenv('AUTH0_REDIRECT_URI') ?: '',
    'auth0_logout_redirect_uri' => getenv('APP_BASE_URL') . getenv('AUTH0_LOGOUT_REDIRECT_URI') ?: '',
]);