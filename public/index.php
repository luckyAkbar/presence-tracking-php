<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

use App\Http\Router;
use App\Container\Container;
use App\Container\ContainerHelper;
use App\Container\ServiceProvider;
use App\Http\RequestContext;

// Minimal session bootstrap for dev: ensure sessions work over HTTP and to /tmp
if (session_status() === PHP_SESSION_NONE) {
    if ((($_SERVER['HTTPS'] ?? '') === '') || ($_SERVER['HTTPS'] ?? '') === 'off') {
        ini_set('session.cookie_secure', '0');
    }
    ini_set('session.save_path', '/tmp');
    session_start();
}

// Set up dependency injection container
$container = new Container();
ServiceProvider::register($container);

// Create IDE-friendly container helper
$app = new ContainerHelper($container);

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

// Auth0 routes - Clean architecture implementation with full IDE support
$router->get('/auth0/login', function () use ($app) {
    $app->auth0Controller()->login();
});

$router->get('/auth0/callback', function () use ($app) {
    $app->auth0Controller()->callback();
});

$router->get('/auth0/logout', function () use ($app) {
    $app->auth0Controller()->logout();
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

$router->get('/api/me', function () use ($app) {
    $app->auth0Controller()->getCurrentUser();
});

// Organization routes
$router->post('/api/organizations', function () use ($app) {
    return $app->authenticateUser()->mustAuthenticate(
        function (RequestContext $ctx) use ($app) {
            $app->organizationController()->registerNewOrganization($ctx);
        }
    );
});

// Invitation routes
$router->post('/api/invitations', function () use ($app) {
    return $app->authenticateUser()->mustAuthenticate(
        function (RequestContext $ctx) use ($app) {
            $app->invitationController()->handleCreateInvitation($ctx);
        }
    );
});

$router->get('/api/invitations/me', function () use ($app) {
    return $app->authenticateUser()->mustAuthenticate(
        function (RequestContext $ctx) use ($app) {
            $app->invitationController()->handleGetInvitationsForRequester($ctx);
        }
    );
});

// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
});

$router->dispatch();