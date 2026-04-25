<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;

/**
 * Renderiza el dashboard principal.
 */
final class DashboardController
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function show(): void
    {
        $currentYear   = (int)date('Y');
        $years         = range($currentYear, (int)$this->config['start_year']);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $cacheRefreshLockedUntil = (int)($_SESSION['cache_refresh_locked_until'] ?? 0);
        $cacheRefreshTtlSeconds  = SessionCache::DEFAULT_TTL;

        extract(compact('currentYear', 'years', 'currentUserId', 'cacheRefreshLockedUntil', 'cacheRefreshTtlSeconds'), EXTR_SKIP);
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
