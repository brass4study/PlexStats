<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Http\Controllers;

use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;

final class CacheRefreshController
{
    private const LOCK_KEY = 'cache_refresh_locked_until';

    public function __construct(
        private readonly SessionCache $cache,
    ) {}

    public function refresh(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $now = time();
        $lockedUntil = (int)($_SESSION[self::LOCK_KEY] ?? 0);

        if ($lockedUntil > $now) {
            http_response_code(429);
            echo json_encode([
                'error' => 'La caché ya fue refrescada recientemente.',
                'cooldownSeconds' => $lockedUntil - $now,
                'lockedUntil' => $lockedUntil,
            ], JSON_THROW_ON_ERROR);
            return;
        }

        $this->cache->invalidateAll();

        $lockedUntil = $now + SessionCache::DEFAULT_TTL;
        $_SESSION[self::LOCK_KEY] = $lockedUntil;

        echo json_encode([
            'ok' => true,
            'cooldownSeconds' => SessionCache::DEFAULT_TTL,
            'lockedUntil' => $lockedUntil,
        ], JSON_THROW_ON_ERROR);
    }
}
