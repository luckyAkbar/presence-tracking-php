<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Http\Router;

// Basic router mapping
$router = new Router();

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

// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
});

$router->dispatch();