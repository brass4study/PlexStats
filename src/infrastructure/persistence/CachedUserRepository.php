<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Persistence;

use PlexStats\Domain\Entities\User;
use PlexStats\Application\Ports\UserRepositoryInterface;
use PlexStats\Infrastructure\Persistence\InMemory\SessionCache;

/**
 * Decorador que añade caché de sesión al repositorio real.
 * Implementa el patrón Decorator: mismo contrato, comportamiento extra.
 */
final class CachedUserRepository implements UserRepositoryInterface
{
    private const USERS_KEY    = 'seer_users';
    private const REQUESTS_KEY = 'seer_req_%d';
    private const RKEYS_KEY    = 'seer_rkeys_%d';

    public function __construct(
        private readonly UserRepositoryInterface $inner,
        private readonly SessionCache            $cache,
    ) {}

    /** @return User[] */
    public function findAll(): array
    {
        if ($this->cache->has(self::USERS_KEY)) {
            return $this->cache->get(self::USERS_KEY); // @phpstan-ignore-line
        }

        $users = $this->inner->findAll();
    $this->cache->set(self::USERS_KEY, $users, SessionCache::DEFAULT_TTL);

        return $users;
    }

    /** @return array<int, int> */
    public function countRequestsByUserForYear(int $year): array
    {
        $key = sprintf(self::REQUESTS_KEY, $year);

        if ($this->cache->has($key)) {
            return $this->cache->get($key); // @phpstan-ignore-line
        }

        $counts = $this->inner->countRequestsByUserForYear($year);
    $this->cache->set($key, $counts, SessionCache::DEFAULT_TTL);

        return $counts;
    }

    /** @return array<int, list<int>> */
    public function getRequestedRatingKeysByUserForYear(int $year): array
    {
        $key = sprintf(self::RKEYS_KEY, $year);

        if ($this->cache->has($key)) {
            return $this->cache->get($key); // @phpstan-ignore-line
        }

        $keys = $this->inner->getRequestedRatingKeysByUserForYear($year);
        $this->cache->set($key, $keys, SessionCache::DEFAULT_TTL);

        return $keys;
    }
}
