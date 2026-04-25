<?php

declare(strict_types=1);

namespace PlexStats\Application\Ports;

use PlexStats\Domain\Entities\MediaRequest;

interface RequestRepositoryInterface
{
    /**
     * @param  array<int, true> $watchedRatingKeys
     * @return MediaRequest[]
     */
    public function findByUserAndYear(int $overseerrUserId, int $year, array $watchedRatingKeys): array;
}