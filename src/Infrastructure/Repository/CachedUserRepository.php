<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Repository;

use PlexStats\Domain\Entity\User;
use PlexStats\Domain\Repository\UserRepositoryInterface;
use PlexStats\Infrastructure\Cache\SessionCache;

/**
 * Decorador que añade caché de sesión al repositorio real.
 * Implementa el patrón Decorator: mismo contrato, comportamiento extra.
 */
final class CachedUserRepository implements UserRepositoryInterface
{
    private const USERS_KEY    = 'seer_users';
    private const REQUESTS_KEY = 'seer_req_%d';
    private const TTL          = 300; // segundos

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
        $this->cache->set(self::USERS_KEY, $users, self::TTL);

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
        $this->cache->set($key, $counts, self::TTL);

        return $counts;
    }
}
