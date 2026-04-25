<?php

declare(strict_types=1);

namespace PlexStats\Application\UseCases;

use PlexStats\Application\Ports\RequestRepositoryInterface;
use PlexStats\Application\Ports\UserRepositoryInterface;
use PlexStats\Application\Ports\WatchRepositoryInterface;
use PlexStats\Domain\Entities\MediaRequest;

final class GetUserRequestsWithWatchStatus
{
    public function __construct(
        private readonly UserRepositoryInterface    $userRepository,
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly ?WatchRepositoryInterface  $watchRepository = null,
    ) {}

    /** @return MediaRequest[] */
    public function execute(int $overseerrUserId, int $year): array
    {
        $watchedByRatingKey = [];

        if ($this->watchRepository !== null) {
            $plexUserId = null;
            foreach ($this->userRepository->findAll() as $user) {
                if ($user->id === $overseerrUserId) {
                    $plexUserId = $user->plexId;
                    break;
                }
            }

            if ($plexUserId !== null) {
                $watchedByRatingKey = $this->watchRepository->getFirstWatchedAtByPlexUser()[$plexUserId] ?? [];
            }
        }

        return $this->requestRepository->findByUserAndYear($overseerrUserId, $year, $watchedByRatingKey);
    }
}
