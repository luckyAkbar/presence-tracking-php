<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

use App\Http\Router;
use App\Config\Config;

// Minimal session bootstrap for dev: ensure sessions work over HTTP and to /tmp
if (session_status() === PHP_SESSION_NONE) {
    if ((($_SERVER['HTTPS'] ?? '') === '') || ($_SERVER['HTTPS'] ?? '') === 'off') {
        ini_set('session.cookie_secure', '0');
    }
    ini_set('session.save_path', '/tmp');
    session_start();
}

// Basic router mapping
$router = new Router();

// Frontend page routes
$router->get('/', function () {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/index.html');
});

$router->get('/logout-success', function () {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/logout-success.html');
});

$router->get('/users/me', function () {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/users/me.html');
});

// Auth0 routes
$router->get('/login', function () {
    App\Auth\AuthService::auth()->clear();

    header("Location: " . App\Auth\AuthService::auth()->login());
    exit;
});

$router->get('/callback', function () {
    $hasAuthenticationFailure = isset($_GET['error']);
    if ($hasAuthenticationFailure) {
        echo "<h1>Authentication failed</h1>";
        echo "<p>Error: " . $_GET['error'] . "</p>";
    }    

    $hasAuthenticated = isset($_GET['state']) && isset($_GET['code']);
    if (!$hasAuthenticated) {
        echo "<h1>Authentication failed</h1>";
        echo "<p>No authentication state or code found</p>";
    }

    try {
        App\Auth\AuthService::auth()->exchange();
        header('Location: ' . Config::getString('auth0_login_success_redirect_uri'));
    } catch (\Throwable $e) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Auth callback failed',
            'message' => $e->getMessage(),
        ]);
    }
});

$router->get('/logout', function () {
    header("Location: " . App\Auth\AuthService::auth()->logout(Config::getString('auth0_logout_redirect_uri')));
    exit;
});

$router->get('/api/ping', function () {
    $dbOk = App\Support\Db::ping();
    $status = $dbOk ? 'ok' : 'degraded';
    header('Content-Type: application/json');
    echo json_encode([
        'service' => 'presence-tracking',
        'status' => $status,
        'db' => $dbOk ? 'ok' : 'fail',
        'time' => gmdate('c'),
    ]);
});

$router->get('/api/me', function () {
    $user = App\Auth\AuthService::auth()->getUser();
    if ($user === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    header('Content-Type: application/json');
    echo json_encode($user);
});

// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
});

$router->dispatch();