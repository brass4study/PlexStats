<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Cache;

/**
 * Caché con soporte TTL almacenada en $_SESSION.
 * No tiene dependencias externas.
 */
final class SessionCache
{
    public function has(string $key): bool
    {
        if (!isset($_SESSION['_cache'][$key])) {
            return false;
        }

        if (time() > ($_SESSION['_cache_exp'][$key] ?? 0)) {
            unset($_SESSION['_cache'][$key], $_SESSION['_cache_exp'][$key]);
            return false;
        }

        return true;
    }

    public function get(string $key): mixed
    {
        return $_SESSION['_cache'][$key] ?? null;
    }

    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        $_SESSION['_cache'][$key]     = $value;
        $_SESSION['_cache_exp'][$key] = time() + $ttl;
    }

    public function invalidate(string $key): void
    {
        unset($_SESSION['_cache'][$key], $_SESSION['_cache_exp'][$key]);
    }
}
