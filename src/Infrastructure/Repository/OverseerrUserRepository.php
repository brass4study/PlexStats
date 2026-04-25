<?php

declare(strict_types=1);

namespace PlexStats\Infrastructure\Repository;

use PlexStats\Domain\Exception\OverseerrException;
use PlexStats\Domain\Entity\User;
use PlexStats\Domain\Repository\UserRepositoryInterface;
use PlexStats\Infrastructure\Http\OverseerrHttpClient;

/**
 * Implementación concreta del repositorio usando la API REST de Overseerr.
 */
final class OverseerrUserRepository implements UserRepositoryInterface
{
    private const PAGE_SIZE    = 100;
    private const MAX_REQUESTS = 10_000; // límite de seguridad

    public function __construct(
        private readonly OverseerrHttpClient $client,
    ) {}

    /** @return User[] */
    public function findAll(): array
    {
        $data = $this->client->get('/user?take=500&skip=0');

        if (empty($data['results'])) {
            throw new OverseerrException('Overseerr no devolvió ningún usuario.');
        }

        return array_map(
            static fn(array $u) => new User(
                id:                (int)$u['id'],
                displayName:       $u['displayName'] ?? $u['plexUsername'] ?? $u['username'] ?? "Usuario #{$u['id']}",
                email:             (string)($u['email'] ?? ''),
                avatar:            (string)($u['avatar'] ?? ''),
                userType:          (int)($u['userType'] ?? 1),
                permissions:       (int)($u['permissions'] ?? 0),
                totalRequestCount: (int)($u['requestCount'] ?? 0),
            ),
            $data['results'],
        );
    }

    /** @return array<int, int> */
    public function countRequestsByUserForYear(int $year): array
    {
        $counts = [];
        $skip   = 0;

        do {
            $resp  = $this->client->get('/request?take=' . self::PAGE_SIZE . "&skip={$skip}&filter=all&sort=added");
            $items = $resp['results'] ?? [];

            foreach ($items as $req) {
                // Filtrar por año usando el prefijo ISO 8601 (e.g. "2026-04-25T...")
                if ((int)substr((string)($req['createdAt'] ?? ''), 0, 4) !== $year) {
                    continue;
                }

                $uid = (int)($req['requestedBy']['id'] ?? 0);
                if ($uid === 0) {
                    continue;
                }

                $counts[$uid] = ($counts[$uid] ?? 0) + 1;
            }

            $total = (int)($resp['pageInfo']['results'] ?? 0);
            $skip += self::PAGE_SIZE;

        } while (!empty($items) && $skip < $total && $skip < self::MAX_REQUESTS);

        return $counts;
    }
}
