<?php

declare(strict_types=1);

namespace PlexStats\Domain\Repository;

use PlexStats\Domain\Entity\User;

interface UserRepositoryInterface
{
    /**
     * Devuelve todos los usuarios registrados en Overseerr.
     *
     * @return User[]
     */
    public function findAll(): array;

    /**
     * Cuenta las peticiones realizadas en $year agrupadas por userId.
     *
     * @return array<int, int>  [ userId => count ]
     */
    public function countRequestsByUserForYear(int $year): array;
}

