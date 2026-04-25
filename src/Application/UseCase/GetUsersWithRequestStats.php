<?php

declare(strict_types=1);

namespace PlexStats\Application\UseCase;

use PlexStats\Domain\Entity\UserRequestStat;
use PlexStats\Domain\Repository\UserRepositoryInterface;

/**
 * Obtiene todos los usuarios con el recuento de peticiones del año indicado.
 * Orquesta el dominio sin conocer detalles de infraestructura.
 */
final class GetUsersWithRequestStats
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return UserRequestStat[]  Ordenados por peticiones del año (desc)
     */
    public function execute(int $year): array
    {
        $users  = $this->userRepository->findAll();
        $counts = $this->userRepository->countRequestsByUserForYear($year);

        $stats = array_map(
            static fn($user) => new UserRequestStat(
                user:              $user,
                yearRequestCount:  $counts[$user->id] ?? 0,
                year:              $year,
            ),
            $users,
        );

        usort($stats, static fn($a, $b) => $b->yearRequestCount <=> $a->yearRequestCount);

        return $stats;
    }
}
