<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Persistence;

use PlexStats\Application\Ports\WatchRepositoryInterface;
use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;

final class CachedWatchRepository implements WatchRepositoryInterface
{
    private const CACHE_KEY = 'tautulli_watched_keys';
    private const TTL       = 300;

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
}