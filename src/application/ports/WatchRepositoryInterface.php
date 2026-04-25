<?php

declare(strict_types=1);

namespace PlexStats\Application\Ports;

interface WatchRepositoryInterface
{
    /**
     * @return array<int, array<int, true>>  [ plexUserId => [ ratingKey => true ] ]
     */
    public function getWatchedRatingKeysByPlexUser(): array;
}