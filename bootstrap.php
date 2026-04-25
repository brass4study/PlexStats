<?php

declare(strict_types=1);

use PlexStats\Application\UseCases\GetUserRequestsWithWatchStatus;
use PlexStats\Infrastructure\Adapters\OverseerrAuthService;
use PlexStats\Infrastructure\Adapters\PlexAuthService;
use PlexStats\Infrastructure\Adapters\OverseerrHttpClient;
use PlexStats\Infrastructure\Adapters\TautulliHttpClient;
use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;
use PlexStats\Infrastructure\Persistence\CachedUserRepository;
use PlexStats\Infrastructure\Persistence\CachedWatchRepository;
use PlexStats\Infrastructure\Persistence\OverseerrRequestRepository;
use PlexStats\Infrastructure\Persistence\OverseerrUserRepository;
use PlexStats\Infrastructure\Persistence\TautulliWatchRepository;
use PlexStats\Infrastructure\Http\Controllers\AuthController;
use PlexStats\Infrastructure\Http\Controllers\DashboardController;
use PlexStats\Infrastructure\Http\Controllers\RequestCountsApiController;
use PlexStats\Infrastructure\Http\Controllers\UserRequestsApiController;
use PlexStats\Infrastructure\Http\Controllers\UsersApiController;
use PlexStats\Infrastructure\Http\Controllers\WatchCountsApiController;
use PlexStats\Infrastructure\Http\AuthMiddleware;
use PlexStats\Infrastructure\Http\Routes\Router;

$nsMap = [
    'PlexStats\\Application\\UseCases\\'                 => __DIR__ . '/src/application/use-cases/',
    'PlexStats\\Application\\Ports\\'                    => __DIR__ . '/src/application/ports/',
    'PlexStats\\Application\\Dto\\'                      => __DIR__ . '/src/application/dto/',
    'PlexStats\\Domain\\Entities\\'                      => __DIR__ . '/src/domain/entities/',
    'PlexStats\\Domain\\Errors\\'                        => __DIR__ . '/src/domain/errors/',
    'PlexStats\\Domain\\Services\\'                      => __DIR__ . '/src/domain/services/',
    'PlexStats\\Domain\\ValueObjects\\'                  => __DIR__ . '/src/domain/value-objects/',
    'PlexStats\\Infrastructure\\Adapters\\'              => __DIR__ . '/src/infrastructure/adapters/',
    'PlexStats\\Infrastructure\\Http\\Controllers\\'     => __DIR__ . '/src/infrastructure/http/controllers/',
    'PlexStats\\Infrastructure\\Http\\Routes\\'          => __DIR__ . '/src/infrastructure/http/routes/',
    'PlexStats\\Infrastructure\\Http\\'                  => __DIR__ . '/src/infrastructure/http/',
    'PlexStats\\Infrastructure\\Persistence\\InMemory\\' => __DIR__ . '/src/infrastructure/persistence/in-memory/',
    'PlexStats\\Infrastructure\\Persistence\\'           => __DIR__ . '/src/infrastructure/persistence/',
    'PlexStats\\Composition\\'                           => __DIR__ . '/src/composition/',
    'PlexStats\\Shared\\'                                => __DIR__ . '/src/shared/',
];

spl_autoload_register(function (string $class) use ($nsMap): void {
    foreach ($nsMap as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file     = $baseDir . $relative . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            return;
        }
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
$watchRepository = null;
if (!empty($config['tautulli_url']) && !empty($config['tautulli_api_key'])) {
    $tautulliClient  = new TautulliHttpClient($config['tautulli_url'], $config['tautulli_api_key']);
    $watchRepository = new CachedWatchRepository(new TautulliWatchRepository($tautulliClient), $cache);
}

$getUserRequests = new GetUserRequestsWithWatchStatus(
    $repository,
    new OverseerrRequestRepository($httpClient),
    $watchRepository,
);

// ── Middleware ────────────────────────────────────────────────
$auth = new AuthMiddleware();

// ── Controllers ───────────────────────────────────────────────
$authCtrl     = new AuthController($plexAuth, $overseerrAuth, $config['app_url']);
$dashCtrl     = new DashboardController($config);
$apiCtrl      = new UsersApiController($repository);
$reqCtrl      = new UserRequestsApiController($getUserRequests);
$reqCntCtrl   = new RequestCountsApiController($repository);
$watchCntCtrl = new WatchCountsApiController($repository, $watchRepository);

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

$router->add('GET', '/api/user-requests', static function () use ($auth, $reqCtrl): void {
    $auth->requireAuth();
    $reqCtrl->index();
});

$router->add('GET', '/api/request-counts', static function () use ($auth, $reqCntCtrl): void {
    $auth->requireAuth();
    $reqCntCtrl->index();
});

$router->add('GET', '/api/watch-counts', static function () use ($auth, $watchCntCtrl): void {
    $auth->requireAuth();
    $watchCntCtrl->index();
});

return $router;
