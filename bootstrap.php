<?php

declare(strict_types=1);

use PlexStats\Application\UseCase\GetUsersWithRequestStats;
use PlexStats\Infrastructure\Auth\OverseerrAuthService;
use PlexStats\Infrastructure\Auth\PlexAuthService;
use PlexStats\Infrastructure\Cache\SessionCache;
use PlexStats\Infrastructure\Http\OverseerrHttpClient;
use PlexStats\Infrastructure\Repository\CachedUserRepository;
use PlexStats\Infrastructure\Repository\OverseerrUserRepository;
use PlexStats\Presentation\Controller\AuthController;
use PlexStats\Presentation\Controller\DashboardController;
use PlexStats\Presentation\Controller\UsersApiController;
use PlexStats\Presentation\Middleware\AuthMiddleware;
use PlexStats\Presentation\Router;

spl_autoload_register(function (string $class): void {
    $prefix = 'PlexStats\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$config = require_once __DIR__ . '/config/app.php';

// ── Session ───────────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ── Infrastructure ────────────────────────────────────────────
$cache      = new SessionCache();
$httpClient = new OverseerrHttpClient($config['overseerr_url'], $config['overseerr_api_key']);
$baseRepo   = new OverseerrUserRepository($httpClient);
$repository = new CachedUserRepository($baseRepo, $cache);

$plexAuth      = new PlexAuthService($config['plex_app_name'], $config['plex_client_id']);
$overseerrAuth = new OverseerrAuthService($config['overseerr_url'], $config['overseerr_api_key']);

// ── Use Cases ─────────────────────────────────────────────────
$getUserStats = new GetUsersWithRequestStats($repository);

// ── Middleware ────────────────────────────────────────────────
$auth = new AuthMiddleware();

// ── Controllers ───────────────────────────────────────────────
$authCtrl = new AuthController($plexAuth, $overseerrAuth, $config['app_url']);
$dashCtrl = new DashboardController($config);
$apiCtrl  = new UsersApiController($getUserStats);

// ── Rutas ─────────────────────────────────────────────────────
$router = new Router();

$router->add('GET', '/', static function () use ($auth, $dashCtrl): void {
    $auth->requireAuth();
    $dashCtrl->show();
});

$router->add('GET', '/login',              [$authCtrl, 'showLogin']);
$router->add('GET', '/auth/plex/init',     [$authCtrl, 'initPlexAuth']);
$router->add('GET', '/auth/plex/callback', [$authCtrl, 'plexCallback']);
$router->add('GET', '/logout',             [$authCtrl, 'logout']);

$router->add('GET', '/api/users', static function () use ($auth, $apiCtrl): void {
    $auth->requireAuth();
    $apiCtrl->index();
});

return $router;
