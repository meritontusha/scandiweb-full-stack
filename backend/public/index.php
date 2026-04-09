<?php

$projectRoot = is_file(__DIR__ . '/../vendor/autoload.php')
    ? dirname(__DIR__)
    : __DIR__;

require_once $projectRoot . '/vendor/autoload.php';

if (file_exists($projectRoot . '/.env')) {
    Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')
)));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$corsOrigin = '*';

function isAllowedOrigin(string $requestOrigin, array $allowedOrigins): bool
{
    foreach ($allowedOrigins as $allowedOrigin) {
        if ($allowedOrigin === $requestOrigin) {
            return true;
        }

        if (str_contains($allowedOrigin, '*')) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($allowedOrigin, '/')) . '$/';

            if (preg_match($pattern, $requestOrigin) === 1) {
                return true;
            }
        }
    }

    return false;
}

if ($requestOrigin !== '' && $allowedOrigins !== []) {
    if (isAllowedOrigin($requestOrigin, $allowedOrigins)) {
        $corsOrigin = $requestOrigin;
    } else {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Origin not allowed']);

        exit;
    }
}

header('Access-Control-Allow-Origin: ' . $corsOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Apollo-Require-Preflight');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);

    exit;
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->post('/graphql', [App\Controller\GraphQL::class, 'endpoint']);
    $r->get('/graphql', [App\Controller\GraphQL::class, 'endpoint']);
});

$routeInfo = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'
);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Not Found';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        header('Allow: ' . implode(', ', $routeInfo[1]));
        echo 'Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        echo call_user_func($handler, $vars);
        break;
}