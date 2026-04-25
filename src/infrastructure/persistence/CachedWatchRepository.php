<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Persistence;

use PlexStats\Application\Ports\WatchRepositoryInterface;
use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;

final class CachedWatchRepository implements WatchRepositoryInterface
{
    private const CACHE_KEY       = 'tautulli_watched_keys';
    private const WATCH_TIMES_KEY = 'tautulli_watch_times';
    private const TTL             = 300;

    public function __construct(
        private readonly WatchRepositoryInterface $inner,
        private readonly SessionCache             $cache,
    ) {}

    /** @return array<int, array<int, true>> */
    public function getWatchedRatingKeysByPlexUser(): array
    {
        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY); // @phpstan-ignore-line
        }

        $keys = $this->inner->getWatchedRatingKeysByPlexUser();
        $this->cache->set(self::CACHE_KEY, $keys, self::TTL);

        return $keys;
    }

    /** @return array<int, array<int, int>> */
    public function getFirstWatchedAtByPlexUser(): array
    {
        if ($this->cache->has(self::WATCH_TIMES_KEY)) {
            return $this->cache->get(self::WATCH_TIMES_KEY); // @phpstan-ignore-line
        }

        $times = $this->inner->getFirstWatchedAtByPlexUser();
        $this->cache->set(self::WATCH_TIMES_KEY, $times, self::TTL);

        return $times;
    }
}
