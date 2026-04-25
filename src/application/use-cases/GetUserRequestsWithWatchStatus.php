<?php

declare(strict_types=1);

namespace PlexStats\Application\UseCases;

use PlexStats\Application\Ports\RequestRepositoryInterface;
use PlexStats\Application\Ports\WatchRepositoryInterface;
use PlexStats\Domain\Entities\MediaRequest;

final class GetUserRequestsWithWatchStatus
{
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly ?WatchRepositoryInterface  $watchRepository = null,
    ) {}

    /** @return MediaRequest[] */
    public function execute(int $overseerrUserId, int $year): array
    {
        $watchedKeys = [];

        if ($this->watchRepository !== null) {
            $allWatched = $this->watchRepository->getWatchedRatingKeysByPlexUser();
            foreach ($allWatched as $userKeys) {
                foreach ($userKeys as $key => $v) {
                    $watchedKeys[$key] = true;
                }
            }
        }

        return $this->requestRepository->findByUserAndYear($overseerrUserId, $year, $watchedKeys);
    }
}
