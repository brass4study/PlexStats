<?php

declare(strict_types=1);

namespace PlexStats\Application\Ports;

use PlexStats\Domain\Entities\User;

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

    /**
     * Devuelve los rating keys de Plex de las solicitudes de cada usuario en $year.
     *
     * @return array<int, list<int>>  [ userId => [ratingKey, ...] ]
     */
    public function getRequestedRatingKeysByUserForYear(int $year): array;
}

